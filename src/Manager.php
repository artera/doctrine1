<?php

namespace Doctrine1;

use ArrayIterator;
use Doctrine1\Serializer\WithSerializers;
use Doctrine1\Deserializer\WithDeserializers;
use Laminas\Validator\ValidatorInterface;
use PDO;

class Manager extends Configurable implements \Countable, \IteratorAggregate
{
    use WithSerializers;
    use WithDeserializers;

    /** array containing all the opened connections */
    protected array $connections = [];

    /** array containing all components that have a bound connection */
    protected array $bound = [];

    protected int $index = 0;

    /** the current connection index */
    protected int|string $currIndex = 0;

    protected ?Query\Registry $queryRegistry = null;

    /** @phpstan-var array<string, class-string<ValidatorInterface>|array{class: class-string<ValidatorInterface>, options: array<string, mixed>}>  */
    protected array $validators = [
        "notnull" => Validator\Notnull::class,
        "date" => \Laminas\Validator\Date::class,
        "time" => Validator\Time::class,
        "timestamp" => Validator\Timestamp::class,

        // backwards compatibility
        "range" => [
            "class" => \Laminas\Validator\Between::class,
            "options" => [
                "min" => -2147483648,
                "max" => 2147483647,
            ],
        ],
        "notblank" => \Laminas\Validator\NotEmpty::class,
        "email" => \Laminas\Validator\EmailAddress::class,
        "ip" => \Laminas\Validator\Ip::class,
        "regexp" => [
            "class" => \Laminas\Validator\Regex::class,
            "options" => [
                "pattern" => "/^/",
            ],
        ],
        "unsigned" => [
            "class" => \Laminas\Validator\GreaterThan::class,
            "options" => [
                "min" => 0,
                "inclusive" => true,
            ],
        ],
    ];

    /** @phpstan-var (class-string<Hydrator\AbstractHydrator>|Hydrator\AbstractHydrator)[] */
    protected array $hydrators = [
        // Keep same order as defined in Doctrine1\HydrationMode
        Hydrator\NoneDriver::class,
        Hydrator\RecordDriver::class,
        Hydrator\ArrayDriver::class,
        Hydrator\ArrayShallowDriver::class,
        Hydrator\ScalarDriver::class,
        Hydrator\SingleScalarDriver::class,
        Hydrator\RecordDriver::class,
    ];

    /** Whether or not the validators from disk have been loaded */
    protected bool $loadedValidatorsFromDisk = false;

    protected static ?Manager $instance;

    private bool $initialized = false;

    /**
     * Sets default attributes values.
     *
     * This method sets default values for all null attributes of this
     * instance. It is idempotent and can only be called one time. Subsequent
     * calls does not alter the attribute values.
     */
    private function setDefaultConfigurables(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $this->setListener(new EventListener());
        $this->setRecordListener(new Record\Listener());
    }

    public function __construct()
    {
        $timezone = new \DateTimeZone(date_default_timezone_get());

        $this->clearSerializers();
        $this->registerSerializer(new Serializer\Boolean(), 10);
        $this->registerSerializer(new Serializer\Enum(), 20);
        $this->registerSerializer(new Serializer\SetFromArray(), 20);
        $this->registerSerializer(new Serializer\DateTime($timezone), 30);
        $this->registerSerializer(new Serializer\JSON(), 40);

        // last resort, these shouldn't exist imho
        $this->registerSerializer(new Serializer\ArrayOrObject(), 0);

        $this->clearDeserializers();
        $this->registerDeserializer(new Deserializer\Boolean(), 10);
        $this->registerDeserializer(new Deserializer\Enum(), 20);
        $this->registerDeserializer(new Deserializer\Set(), 20);
        $this->registerDeserializer(new Deserializer\DateTimeImmutable($timezone), 20);
        $this->registerDeserializer(new Deserializer\JSON(), 40);
    }

    /**
     * Returns an instance of this class
     * (this class uses the singleton pattern)
     *
     * @return Manager
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Reset the internal static instance
     *
     * @return void
     */
    public static function resetInstance()
    {
        if (self::$instance) {
            self::$instance->reset();
            self::$instance = null;
        }
    }

    /**
     * Reset this instance of the manager
     *
     * @return void
     */
    public function reset()
    {
        foreach ($this->connections as $conn) {
            $conn->close();
        }
        $this->connections = [];
        $this->queryRegistry = null;
        $this->bound = [];
        $this->validators = [];
        $this->loadedValidatorsFromDisk = false;
        $this->index = 0;
        $this->currIndex = 0;
        $this->initialized = false;
    }

    /**
     * Lazy-initializes the query registry object and returns it
     *
     * @return Query\Registry
     */
    public function getQueryRegistry()
    {
        if (!isset($this->queryRegistry)) {
            $this->queryRegistry = new Query\Registry();
        }
        return $this->queryRegistry;
    }

    /**
     * Sets the query registry
     *
     * @return $this     this object
     */
    public function setQueryRegistry(Query\Registry $registry)
    {
        $this->queryRegistry = $registry;

        return $this;
    }

    /**
     * Open a new connection. If the adapter parameter is set this method acts as
     * a short cut for Manager::getInstance()->openConnection($adapter, $name);
     *
     * if the adapter parameter is not set this method acts as
     * a short cut for Manager::getInstance()->getCurrentConnection()
     *
     * @param  PDO|array|string|null $adapter database driver, DSN or array of connection options
     * @param  string                                           $name    name of the connection, if empty numeric key is used
     * @throws Manager\Exception                          if trying to bind a connection with an existing name
     * @return Connection
     */
    public static function connection(PDO|array|string|null $adapter = null, $name = null)
    {
        if ($adapter === null) {
            return Manager::getInstance()->getCurrentConnection();
        } else {
            return Manager::getInstance()->openConnection($adapter, $name);
        }
    }

    /**
     * Opens a new connection and saves it to Manager->connections
     *
     * @param  PDO|array|string $adapter    database driver, DSN or array of connection options
     * @param  string                                      $name       name of the connection, if empty numeric key is used
     * @param  bool                                        $setCurrent
     * @throws Manager\Exception                          if trying to bind a connection with an existing name
     * @throws Manager\Exception                          if trying to open connection for unknown driver
     * @return Connection
     */
    public function openConnection(PDO|array|string $adapter, $name = null, $setCurrent = true)
    {
        if ($adapter instanceof PDO) {
            $driverName = $adapter->getAttribute(PDO::ATTR_DRIVER_NAME);
        } elseif (is_array($adapter)) {
            if (!isset($adapter[0])) {
                throw new Manager\Exception("Empty data source name given.");
            }
            $e = explode(":", $adapter[0]);

            if ($e[0] == "uri") {
                $e[0] = "odbc";
            }

            $parts["dsn"] = $adapter[0];
            $parts["scheme"] = $e[0];
            $parts["user"] = isset($adapter[1]) ? $adapter[1] : null;
            $parts["pass"] = isset($adapter[2]) ? $adapter[2] : null;
            $driverName = $e[0];
            $adapter = $parts;
        } else {
            $parts = $this->parseDsn($adapter);
            $driverName = $parts["scheme"];
            $adapter = $parts;
        }

        // Decode adapter information
        if (is_array($adapter)) {
            foreach ($adapter as $key => $value) {
                $adapter[$key] = $value ? urldecode($value) : null;
            }
        }

        // initialize the default attributes
        $this->setDefaultConfigurables();

        if ($name !== null) {
            $name = (string) $name;
            if (isset($this->connections[$name])) {
                if ($setCurrent) {
                    $this->currIndex = $name;
                }
                return $this->connections[$name];
            }
        } else {
            $name = $this->index;
            $this->index++;
        }

        $className = match ($driverName) {
            "mysql" => Connection\Mysql::class,
            "mysqli" => Connection\Mysql::class,
            "sqlite" => Connection\Sqlite::class,
            "pgsql" => Connection\Pgsql::class,
            "mock" => Connection\Mock::class,
            default => throw new Manager\Exception("Unknown driver $driverName"),
        };

        $conn = new $className($this, $adapter);
        $conn->setName((string) $name);

        $this->connections[$name] = $conn;

        if ($setCurrent) {
            $this->currIndex = $name;
        }
        return $this->connections[$name];
    }

    /**
     * Parse a pdo style dsn in to an array of parts
     *
     * @param  string $dsn A DSN string (colon delimited parts)
     * @return array The array parsed
     * @phpstan-return array{
     *   dsn: string,
     *   scheme: string,
     *   host: ?string,
     *   port: ?string,
     *   user: ?string,
     *   pass: ?string,
     *   path: ?string,
     *   query: ?string,
     *   fragment: ?string,
     *   unix_socket: ?string,
     *   dbname?: string,
     * }
     */
    public function parsePdoDsn(string $dsn): array
    {
        $parts = [
            "dsn" => null,
            "scheme" => null,
            "host" => null,
            "port" => null,
            "user" => null,
            "pass" => null,
            "path" => null,
            "query" => null,
            "fragment" => null,
            "unix_socket" => null,
        ];

        $e = explode(":", $dsn, 2);
        $parts["scheme"] = $e[0] ?? "";
        $parts["dsn"] = $dsn;

        $e = explode(";", $e[1] ?? "");
        foreach ($e as $string) {
            $e2 = explode("=", $string, 2);
            if (count($e2) === 2) {
                $parts[$e2[0]] = $e2[1];
            }
        }

        /**
         * @phpstan-var array{
         *   dsn: string,
         *   scheme: string,
         *   host: ?string,
         *   port: ?string,
         *   user: ?string,
         *   pass: ?string,
         *   path: ?string,
         *   query: ?string,
         *   fragment: ?string,
         *   unix_socket: ?string,
         * }
         */
        return $parts;
    }

    /**
     * Build the blank dsn parts array used with parseDsn()
     *
     * @see    parseDsn()
     * @param  string $dsn
     * @return array $parts
     * @throws Manager\Exception
     */
    protected function buildDsnPartsArray(string $dsn): array
    {
        // fix sqlite dsn so that it will parse correctly
        $dsn = str_replace("////", "/", $dsn);
        $dsn = str_replace("\\", "/", $dsn);
        $dsn = preg_replace("/\/\/\/(.*):\//", '//$1:/', $dsn) ?? $dsn;

        // silence any warnings
        $parts = @parse_url($dsn) ?: [];

        $names = ["dsn", "scheme", "host", "port", "user", "pass", "path", "query", "fragment"];

        foreach ($names as $name) {
            if (!isset($parts[$name])) {
                $parts[$name] = null;
            }
        }

        if (count($parts) == 0 || !isset($parts["scheme"])) {
            throw new Manager\Exception("Could not parse dsn");
        }

        return $parts;
    }

    /**
     * Parse a Doctrine style dsn string in to an array of parts
     *
     * @param  string $dsn
     * @return array Parsed contents of DSN
     * @throws Manager\Exception
     */
    public function parseDsn(string $dsn): array
    {
        $parts = $this->buildDsnPartsArray($dsn);

        switch ($parts["scheme"]) {
            case "sqlite":
            case "sqlite2":
            case "sqlite3":
                if (isset($parts["host"]) && $parts["host"] == ":memory") {
                    $parts["database"] = ":memory:";
                    $parts["dsn"] = "sqlite::memory:";
                } else {
                    //fix windows dsn we have to add host: to path and set host to null
                    if (isset($parts["host"])) {
                        $parts["path"] = $parts["host"] . ":" . $parts["path"];
                        $parts["host"] = null;
                    }
                    $parts["database"] = $parts["path"];
                    $parts["dsn"] = $parts["scheme"] . ":" . $parts["path"];
                }

                break;

            case "mysql":
                if (!isset($parts["path"]) || $parts["path"] == "/") {
                    throw new Manager\Exception("No database available in data source name");
                }
                $parts["database"] = substr($parts["path"], 1);

                if (!isset($parts["host"])) {
                    throw new Manager\Exception("No hostname set in data source name");
                }

                $parts["dsn"] =
                    $parts["scheme"] . ":host=" . $parts["host"] . (isset($parts["port"]) ? ";port=" . $parts["port"] : null) . ";dbname=" . $parts["database"];

                if (!empty($parts["query"])) {
                    parse_str($parts["query"], $queryParts);
                    if (isset($queryParts["charset"])) {
                        if (is_array($queryParts["charset"])) {
                            throw new Manager\Exception("Cannot specify charset array in DSN");
                        }
                        $parts["dsn"] .= ";charset=" . $queryParts["charset"];
                        unset($queryParts["charset"]);
                        $parts["query"] = http_build_query($queryParts);
                    }
                }
                break;
            case "pgsql":
            case "mock":
                if (!isset($parts["path"]) || $parts["path"] == "/") {
                    throw new Manager\Exception("No database available in data source name");
                }
                $parts["database"] = substr($parts["path"], 1);

                if (!isset($parts["host"])) {
                    throw new Manager\Exception("No hostname set in data source name");
                }

                $parts["dsn"] =
                    $parts["scheme"] . ":host=" . $parts["host"] . (isset($parts["port"]) ? ";port=" . $parts["port"] : null) . ";dbname=" . $parts["database"];

                break;
            default:
                $parts["dsn"] = $dsn;
        }

        return $parts;
    }

    /**
     * Get the connection instance for the passed name
     *
     * @param  string $name name of the connection, if empty numeric key is used
     * @throws Manager\Exception   if trying to get a non-existent connection
     */
    public function getConnection(string $name): Connection
    {
        if (!isset($this->connections[$name])) {
            throw new Manager\Exception("Unknown connection: " . $name);
        }

        return $this->connections[$name];
    }

    /**
     * Get the name of the passed connection instance
     *
     * @param Connection $conn connection object to be searched for
     *
     * @return int|string|null the name of the connection
     */
    public function getConnectionName(Connection $conn): int|string|null
    {
        $res = array_search($conn, $this->connections, true);
        return $res === false ? null : $res;
    }

    /**
     * Binds given component to given connection
     * this means that when ever the given component uses a connection
     * it will be using the bound connection instead of the current connection
     */
    public function bindComponent(string $componentName, string $connectionName): void
    {
        $this->bound[$componentName] = $connectionName;
    }

    /**
     * Get the connection instance for the specified component
     */
    public function getConnectionForComponent(string $componentName): Connection
    {
        if (isset($this->bound[$componentName])) {
            return $this->getConnection($this->bound[$componentName]);
        }

        return $this->getCurrentConnection();
    }

    /**
     * Check if a component is bound to a connection
     */
    public function hasConnectionForComponent(string $componentName = null): bool
    {
        return isset($this->bound[$componentName]);
    }

    /**
     * Closes the specified connection
     */
    public function closeConnection(Connection $connection): void
    {
        $connection->close();

        $key = array_search($connection, $this->connections, true);

        if ($key !== false) {
            unset($this->connections[$key]);

            if ($key === $this->currIndex) {
                $key = key($this->connections);
                $this->currIndex = $key !== null ? $key : 0;
            }
        }

        unset($connection);
    }

    /**
     * Returns all opened connections
     * @phpstan-return Connection[]
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Sets the current connection to $key
     *
     * @param string $key the connection key
     * @throws Manager\Exception
     */
    public function setCurrentConnection(string $key): void
    {
        $key = (string) $key;
        if (!isset($this->connections[$key])) {
            throw new Manager\Exception("Connection key '$key' does not exist.");
        }
        $this->currIndex = $key;
    }

    /**
     * Whether or not the manager contains specified connection
     *
     * @param mixed $key the connection key
     */
    public function contains($key): bool
    {
        return isset($this->connections[$key]);
    }

    /**
     * Returns the number of opened connections
     */
    public function count(): int
    {
        return count($this->connections);
    }

    /**
     * Returns an ArrayIterator that iterates through all connections
     * @phpstan-return ArrayIterator<Connection>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->connections);
    }

    /**
     * Get the current connection instance
     *
     * @throws Connection\Exception if there are no open connections
     */
    public function getCurrentConnection(): Connection
    {
        $i = $this->currIndex;
        if (!isset($this->connections[$i])) {
            throw new Connection\Exception("There is no open connection");
        }
        return $this->connections[$i];
    }

    /**
     * Creates databases for all existing connections
     *
     * @param string|array $specifiedConnections Array of connections you wish to create the database for
     */
    public function createDatabases(string|array $specifiedConnections = []): void
    {
        if (!is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }

        foreach ($this as $name => $connection) {
            if (!empty($specifiedConnections) && !in_array($name, $specifiedConnections)) {
                continue;
            }

            $connection->createDatabase();
        }
    }

    /**
     * Drops databases for all existing connections
     *
     * @param string|array $specifiedConnections Array of connections you wish to drop the database for
     */
    public function dropDatabases(string|array $specifiedConnections = []): void
    {
        if (!is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }

        foreach ($this as $name => $connection) {
            if (!empty($specifiedConnections) && !in_array($name, $specifiedConnections)) {
                continue;
            }

            $connection->dropDatabase();
        }
    }

    /** @phpstan-return array<string, class-string<ValidatorInterface>|array{class: class-string<ValidatorInterface>, options: array<string, mixed>}> */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * @phpstan-param class-string<ValidatorInterface> $validatorClass
     * @phpstan-param array<string, mixed> $options
     * */
    public function registerValidator(string $alias, string $validatorClass, array $options = []): void
    {
        $this->validators[$alias] = [
            "class" => $validatorClass,
            "options" => $options,
        ];
    }

    /**
     * Register a new driver for hydration
     *
     * @phpstan-param class-string<Hydrator\AbstractHydrator>|Hydrator\AbstractHydrator $class
     */
    public function registerHydrator(int|string $name, string|Hydrator\AbstractHydrator $class): void
    {
        $this->hydrators[$name] = $class;
    }

    /**
     * Get all registered hydrators
     *
     * @phpstan-return (class-string<Hydrator\AbstractHydrator>|Hydrator\AbstractHydrator)[]
     */
    public function getHydrators()
    {
        return $this->hydrators;
    }
}
