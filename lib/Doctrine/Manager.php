<?php

use Doctrine1\Serializer\WithSerializers;
use Laminas\Validator\ValidatorInterface;
use Laminas\Validator;

class Doctrine_Manager extends Doctrine_Configurable implements Countable, IteratorAggregate
{
    use WithSerializers;

    /** array containing all the opened connections */
    protected array $connections = [];

    /** array containing all components that have a bound connection */
    protected array $bound = [];

    protected int $index = 0;

    /** the current connection index */
    protected int|string $currIndex = 0;

    protected ?Doctrine_Query_Registry $queryRegistry = null;

    /** @phpstan-var array<string, class-string<ValidatorInterface>|array{class: class-string<ValidatorInterface>, options: array<string, mixed>}>  */
    protected array $validators = [
        'notnull' => Doctrine_Validator_Notnull::class,
        'date' => Validator\Date::class,
        'time' => Doctrine_Validator_Time::class,
        'timestamp' => Doctrine_Validator_Timestamp::class,

        // backwards compatibility
        'range' => [
            'class' => Validator\Between::class,
            'options' => [
                'min' => -2147483648,
                'max' => 2147483647,
            ],
        ],
        'notblank' => Validator\NotEmpty::class,
        'email' => Validator\EmailAddress::class,
        'ip' => Validator\Ip::class,
        'regexp' => [
            'class' => Validator\Regex::class,
            'options' => [
                'pattern' => '/^/',
            ],
        ],
        'unsigned' => [
            'class' => Validator\GreaterThan::class,
            'options' => [
                'min' => 0,
                'inclusive' => true,
            ],
        ],
    ];

    /** @phpstan-var (class-string<Doctrine_Hydrator_Abstract>|Doctrine_Hydrator_Abstract)[] */
    protected array $hydrators = [
        Doctrine_Core::HYDRATE_ARRAY         => Doctrine_Hydrator_ArrayDriver::class,
        Doctrine_Core::HYDRATE_RECORD        => Doctrine_Hydrator_RecordDriver::class,
        Doctrine_Core::HYDRATE_NONE          => Doctrine_Hydrator_NoneDriver::class,
        Doctrine_Core::HYDRATE_SCALAR        => Doctrine_Hydrator_ScalarDriver::class,
        Doctrine_Core::HYDRATE_SINGLE_SCALAR => Doctrine_Hydrator_SingleScalarDriver::class,
        Doctrine_Core::HYDRATE_ON_DEMAND     => Doctrine_Hydrator_RecordDriver::class,
        Doctrine_Core::HYDRATE_ARRAY_SHALLOW => Doctrine_Hydrator_ArrayShallowDriver::class,
    ];

    /** @phpstan-var array<string, class-string<Doctrine_Connection>> */
    protected array $connectionDrivers = [
        'mysql'  => Doctrine_Connection_Mysql::class,
        'mysqli' => Doctrine_Connection_Mysql::class,
        'sqlite' => Doctrine_Connection_Sqlite::class,
        'pgsql'  => Doctrine_Connection_Pgsql::class,
        'mock'   => Doctrine_Connection_Mock::class,
    ];

    /** Whether or not the validators from disk have been loaded */
    protected bool $loadedValidatorsFromDisk = false;

    protected static ?Doctrine_Manager $instance;

    private bool $initialized = false;

    /**
     * Sets default attributes values.
     *
     * This method sets default values for all null attributes of this
     * instance. It is idempotent and can only be called one time. Subsequent
     * calls does not alter the attribute values.
     *
     * @return boolean      true if inizialization was executed
     */
    public function setDefaultAttributes()
    {
        if (!$this->initialized) {
            $this->initialized = true;
            $attributes         = [
                Doctrine_Core::ATTR_CACHE                      => null,
                Doctrine_Core::ATTR_QUERY_CACHE                => null,
                Doctrine_Core::ATTR_LOAD_REFERENCES            => true,
                Doctrine_Core::ATTR_LISTENER                   => new Doctrine_EventListener(),
                Doctrine_Core::ATTR_RECORD_LISTENER            => new Doctrine_Record_Listener(),
                Doctrine_Core::ATTR_VALIDATE                   => Doctrine_Core::VALIDATE_NONE,
                Doctrine_Core::ATTR_QUERY_LIMIT                => Doctrine_Core::LIMIT_RECORDS,
                Doctrine_Core::ATTR_IDXNAME_FORMAT             => '%s_idx',
                Doctrine_Core::ATTR_SEQNAME_FORMAT             => '%s_seq',
                Doctrine_Core::ATTR_TBLNAME_FORMAT             => '%s',
                Doctrine_Core::ATTR_FKNAME_FORMAT              => '%s',
                Doctrine_Core::ATTR_QUOTE_IDENTIFIER           => false,
                Doctrine_Core::ATTR_SEQCOL_NAME                => 'id',
                Doctrine_Core::ATTR_PORTABILITY                => Doctrine_Core::PORTABILITY_NONE,
                Doctrine_Core::ATTR_EXPORT                     => Doctrine_Core::EXPORT_ALL,
                Doctrine_Core::ATTR_DECIMAL_PLACES             => 2,
                Doctrine_Core::ATTR_DEFAULT_PARAM_NAMESPACE    => 'doctrine',
                Doctrine_Core::ATTR_AUTOLOAD_TABLE_CLASSES     => false,
                Doctrine_Core::ATTR_USE_DQL_CALLBACKS          => false,
                Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE     => false,
                Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS    => false,
                Doctrine_Core::ATTR_DEFAULT_IDENTIFIER_OPTIONS => [],
                Doctrine_Core::ATTR_DEFAULT_COLUMN_OPTIONS     => [],
                Doctrine_Core::ATTR_HYDRATE_OVERWRITE          => true,
                Doctrine_Core::ATTR_QUERY_CLASS                => 'Doctrine_Query',
                Doctrine_Core::ATTR_COLLECTION_CLASS           => 'Doctrine_Collection',
                Doctrine_Core::ATTR_TABLE_CLASS                => 'Doctrine_Table',
                Doctrine_Core::ATTR_CASCADE_SAVES              => true,
                Doctrine_Core::ATTR_TABLE_CLASS_FORMAT         => '%sTable'
            ];
            foreach ($attributes as $attribute => $value) {
                $old = $this->getAttribute($attribute);
                if ($old === null) {
                    $this->setAttribute($attribute, $value);
                }
            }
            return true;
        }
        return false;
    }

    public function __construct()
    {
        $this->clearSerializers();
        $this->registerSerializer(new \Doctrine1\Serializer\Boolean(), 10);
        $this->registerSerializer(new \Doctrine1\Serializer\SetFromArray(), 20);
        $this->registerSerializer(new \Doctrine1\Serializer\DateTime(), 30);

        // last resort, these shouldn't exist imho
        $this->registerSerializer(new \Doctrine1\Serializer\Gzip(), 0);
        $this->registerSerializer(new \Doctrine1\Serializer\ArrayOrObject(), 0);
    }

    /**
     * Returns an instance of this class
     * (this class uses the singleton pattern)
     *
     * @return Doctrine_Manager
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
        $this->connections              = [];
        $this->queryRegistry            = null;
        $this->bound                    = [];
        $this->validators               = [];
        $this->loadedValidatorsFromDisk = false;
        $this->index                    = 0;
        $this->currIndex                = 0;
        $this->initialized              = false;
    }

    /**
     * Lazy-initializes the query registry object and returns it
     *
     * @return Doctrine_Query_Registry
     */
    public function getQueryRegistry()
    {
        if (!isset($this->queryRegistry)) {
            $this->queryRegistry = new Doctrine_Query_Registry();
        }
        return $this->queryRegistry;
    }

    /**
     * Sets the query registry
     *
     * @return $this     this object
     */
    public function setQueryRegistry(Doctrine_Query_Registry $registry)
    {
        $this->queryRegistry = $registry;

        return $this;
    }

    /**
     * Open a new connection. If the adapter parameter is set this method acts as
     * a short cut for Doctrine_Manager::getInstance()->openConnection($adapter, $name);
     *
     * if the adapter parameter is not set this method acts as
     * a short cut for Doctrine_Manager::getInstance()->getCurrentConnection()
     *
     * @param  PDO|array|string|null $adapter database driver, DSN or array of connection options
     * @param  string                                           $name    name of the connection, if empty numeric key is used
     * @throws Doctrine_Manager_Exception                          if trying to bind a connection with an existing name
     * @return Doctrine_Connection
     */
    public static function connection(PDO|array|string|null $adapter = null, $name = null)
    {
        if ($adapter === null) {
            return Doctrine_Manager::getInstance()->getCurrentConnection();
        } else {
            return Doctrine_Manager::getInstance()->openConnection($adapter, $name);
        }
    }

    /**
     * Opens a new connection and saves it to Doctrine_Manager->connections
     *
     * @param  PDO|array|string $adapter    database driver, DSN or array of connection options
     * @param  string                                      $name       name of the connection, if empty numeric key is used
     * @param  bool                                        $setCurrent
     * @throws Doctrine_Manager_Exception                          if trying to bind a connection with an existing name
     * @throws Doctrine_Manager_Exception                          if trying to open connection for unknown driver
     * @return Doctrine_Connection
     */
    public function openConnection(PDO|array|string $adapter, $name = null, $setCurrent = true)
    {
        if ($adapter instanceof PDO) {
            $driverName = $adapter->getAttribute(Doctrine_Core::ATTR_DRIVER_NAME);
        } elseif (is_array($adapter)) {
            if (!isset($adapter[0])) {
                throw new Doctrine_Manager_Exception('Empty data source name given.');
            }
            $e = explode(':', $adapter[0]);

            if ($e[0] == 'uri') {
                $e[0] = 'odbc';
            }

            $parts['dsn']    = $adapter[0];
            $parts['scheme'] = $e[0];
            $parts['user']   = (isset($adapter[1])) ? $adapter[1] : null;
            $parts['pass']   = (isset($adapter[2])) ? $adapter[2] : null;
            $driverName      = $e[0];
            $adapter         = $parts;
        } else {
            $parts      = $this->parseDsn($adapter);
            $driverName = $parts['scheme'];
            $adapter    = $parts;
        }

        // Decode adapter information
        if (is_array($adapter)) {
            foreach ($adapter as $key => $value) {
                $adapter[$key] = $value ? urldecode($value):null;
            }
        }

        // initialize the default attributes
        $this->setDefaultAttributes();

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

        if (!isset($this->connectionDrivers[$driverName])) {
            throw new Doctrine_Manager_Exception('Unknown driver ' . $driverName);
        }

        $className = $this->connectionDrivers[$driverName];
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
            'dsn' => null,
            'scheme' => null,
            'host' => null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
            'fragment' => null,
            'unix_socket' => null,
        ];

        $e = explode(':', $dsn, 2);
        $parts['scheme'] = $e[0] ?? '';
        $parts['dsn'] = $dsn;

        $e = explode(';', $e[1] ?? '');
        foreach ($e as $string) {
            $e2 = explode('=', $string, 2);
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
     */
    protected function buildDsnPartsArray(string $dsn): array
    {
        // fix sqlite dsn so that it will parse correctly
        $dsn = str_replace('////', '/', $dsn);
        $dsn = str_replace('\\', '/', $dsn);
        $dsn = preg_replace("/\/\/\/(.*):\//", '//$1:/', $dsn) ?? $dsn;

        // silence any warnings
        $parts = @parse_url($dsn);

        $names = ['dsn', 'scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment'];

        foreach ($names as $name) {
            if (!isset($parts[$name])) {
                $parts[$name] = null;
            }
        }

        if (count($parts) == 0 || !isset($parts['scheme'])) {
            throw new Doctrine_Manager_Exception('Could not parse dsn');
        }

        return $parts;
    }

    /**
     * Parse a Doctrine style dsn string in to an array of parts
     *
     * @param  string $dsn
     * @return array Parsed contents of DSN
     */
    public function parseDsn(string $dsn): array
    {
        $parts = $this->buildDsnPartsArray($dsn);

        switch ($parts['scheme']) {
            case 'sqlite':
            case 'sqlite2':
            case 'sqlite3':
                if (isset($parts['host']) && $parts['host'] == ':memory') {
                    $parts['database'] = ':memory:';
                    $parts['dsn']      = 'sqlite::memory:';
                } else {
                    //fix windows dsn we have to add host: to path and set host to null
                    if (isset($parts['host'])) {
                        $parts['path'] = $parts['host'] . ':' . $parts['path'];
                        $parts['host'] = null;
                    }
                    $parts['database'] = $parts['path'];
                    $parts['dsn']      = $parts['scheme'] . ':' . $parts['path'];
                }

                break;

            case 'mysql':
                if (!isset($parts['path']) || $parts['path'] == '/') {
                    throw new Doctrine_Manager_Exception('No database available in data source name');
                }
                if (isset($parts['path'])) {
                    $parts['database'] = substr($parts['path'], 1);
                }
                if (!isset($parts['host'])) {
                    throw new Doctrine_Manager_Exception('No hostname set in data source name');
                }

                $parts['dsn'] = $parts['scheme'] . ':host='
                          . $parts['host'] . (isset($parts['port']) ? ';port=' . $parts['port']:null) . ';dbname='
                          . $parts['database'];

                if (!empty($parts['query'])) {
                    parse_str($parts['query'], $queryParts);
                    if (isset($queryParts['charset'])) {
                        $parts['dsn'] .= ';charset=' . $queryParts['charset'];
                        unset($queryParts['charset']);
                        $parts['query'] = http_build_query($queryParts);
                    }
                }
                break;
            case 'pgsql':
            case 'mock':
                if (!isset($parts['path']) || $parts['path'] == '/') {
                    throw new Doctrine_Manager_Exception('No database available in data source name');
                }
                if (isset($parts['path'])) {
                    $parts['database'] = substr($parts['path'], 1);
                }
                if (!isset($parts['host'])) {
                    throw new Doctrine_Manager_Exception('No hostname set in data source name');
                }

                $parts['dsn'] = $parts['scheme'] . ':host='
                          . $parts['host'] . (isset($parts['port']) ? ';port=' . $parts['port']:null) . ';dbname='
                          . $parts['database'];

                break;
            default:
                $parts['dsn'] = $dsn;
        }

        return $parts;
    }

    /**
     * Get the connection instance for the passed name
     *
     * @param  string $name name of the connection, if empty numeric key is used
     * @throws Doctrine_Manager_Exception   if trying to get a non-existent connection
     */
    public function getConnection(string $name): Doctrine_Connection
    {
        if (!isset($this->connections[$name])) {
            throw new Doctrine_Manager_Exception('Unknown connection: ' . $name);
        }

        return $this->connections[$name];
    }

    /**
     * Get the name of the passed connection instance
     *
     * @param Doctrine_Connection $conn connection object to be searched for
     *
     * @return int|string|null the name of the connection
     */
    public function getConnectionName(Doctrine_Connection $conn): int|string|null
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
    public function getConnectionForComponent(string $componentName): Doctrine_Connection
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
    public function closeConnection(Doctrine_Connection $connection): void
    {
        $connection->close();

        $key = array_search($connection, $this->connections, true);

        if ($key !== false) {
            unset($this->connections[$key]);

            if ($key === $this->currIndex) {
                $key              = key($this->connections);
                $this->currIndex = ($key !== null) ? $key : 0;
            }
        }

        unset($connection);
    }

    /**
     * Returns all opened connections
     * @phpstan-return Doctrine_Connection[]
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Sets the current connection to $key
     *
     * @param string $key the connection key
     * @throws Doctrine_Manager_Exception
     */
    public function setCurrentConnection(string $key): void
    {
        $key = (string) $key;
        if (!isset($this->connections[$key])) {
            throw new Doctrine_Manager_Exception("Connection key '$key' does not exist.");
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
     * @phpstan-return ArrayIterator<Doctrine_Connection>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->connections);
    }

    /**
     * Get the current connection instance
     *
     * @throws Doctrine_Connection_Exception if there are no open connections
     */
    public function getCurrentConnection(): Doctrine_Connection
    {
        $i = $this->currIndex;
        if (!isset($this->connections[$i])) {
            throw new Doctrine_Connection_Exception('There is no open connection');
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
        $this->validators[$alias] = $validatorClass;
    }

    /**
     * Register a new driver for hydration
     *
     * @phpstan-param class-string<Doctrine_Hydrator_Abstract>|Doctrine_Hydrator_Abstract $class
     */
    public function registerHydrator(int|string $name, string|Doctrine_Hydrator_Abstract $class): void
    {
        $this->hydrators[$name] = $class;
    }

    /**
     * Get all registered hydrators
     *
     * @phpstan-return (class-string<Doctrine_Hydrator_Abstract>|Doctrine_Hydrator_Abstract)[]
     */
    public function getHydrators()
    {
        return $this->hydrators;
    }

    /**
     * Register a custom connection driver
     *
     * @phpstan-param class-string<Doctrine_Connection> $class
     */
    public function registerConnectionDriver(string $name, string $class): void
    {
        $this->connectionDrivers[$name] = $class;
    }

    /**
     * Get all the available connection drivers
     *
     * @phpstan-return array<string, class-string<Doctrine_Connection>>
     */
    public function getConnectionDrivers(): array
    {
        return $this->connectionDrivers;
    }
}
