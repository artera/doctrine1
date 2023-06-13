<?php

namespace Doctrine1;

use ArrayIterator;
use Doctrine1\Adapter\Mock;
use Doctrine1\Transaction\SavePoint;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * From $modules array
 * @property   Formatter $formatter
 * @property   Connection\UnitOfWork $unitOfWork
 * @property   Transaction $transaction
 * @property   Expression\Driver $expression
 * @property   DataDict $dataDict
 * @property   Export $export
 * @property   Import $import
 * @property   Sequence $sequence
 *
 * From $properties array
 * @property   array $identifier_quoting
 * @property   int $max_identifier_length
 * @property   array $sql_comments
 * @property   string $sql_file_delimiter
 * @property   array $string_quoting
 * @property   int $varchar_max_length
 * @property   array $wildcards
 * Not initially defined, but added later
 * @property   string|array $dsn
 */
abstract class Connection extends Configurable implements \Countable, \IteratorAggregate, \Serializable
{
    protected ?PDO $dbh = null;

    public ?Casing $fieldCase = null;

    /**
     * @var array $tables                       an array containing all the initialized Table objects
     *                                          keys representing Table component names and values as Table objects
     */
    protected array $tables = [];

    /**
     * Name of the connection
     */
    protected string $name;

    /**
     * The name of this connection driver.
     */
    protected string $driverName;

    /**
     * @var array $supported                    an array containing all features this driver supports,
     *                                          keys representing feature names and values as
     *                                          one of the following (true, false, 'emulated')
     */
    protected array $supported = [];

    /**
     * @phpstan-var array<int, mixed> $pendingAttributes
     * @var array $pendingAttributes            An array of pending attributes. When setting attributes
     *                                          no connection is needed. When connected all the pending
     *                                          attributes are passed to the underlying adapter (usually PDO) instance.
     */
    protected array $pendingAttributes = [];

    /**
     * @var array $modules                      an array containing all modules
     *              transaction                 Transaction driver, handles savepoint and transaction isolation abstraction
     *
     *              expression                  Expression\Driver, handles expression abstraction
     *
     *              dataDict                    DataDict driver, handles datatype abstraction
     *
     *              export                      Export driver, handles db structure modification abstraction (contains
     *                                          methods such as alterTable, createConstraint etc.)
     *              import                      Import driver, handles db schema reading
     *
     *              sequence                    Sequence driver, handles sequential id generation and retrieval
     *
     *              unitOfWork                  Connection\UnitOfWork handles many orm functionalities such as object
     *                                          deletion and saving
     *
     *              formatter                   Formatter handles data formatting, quoting and escaping
     *
     * @see Connection::__get()
     * @see DataDict
     * @see Expression\Driver
     * @see Export
     * @see Transaction
     * @see Sequence
     * @see Connection\UnitOfWork
     * @see Formatter
     */
    private array $modules = [
        'transaction' => false,
        'expression'  => false,
        'dataDict'    => false,
        'export'      => false,
        'import'      => false,
        'sequence'    => false,
        'unitOfWork'  => false,
        'formatter'   => false,
        'util'        => false,
    ];

    /**
     * @var array $properties               an array of connection properties
     */
    protected array $properties = [
        'sql_comments' => [
            ['start' => '--', 'end' => "\n", 'escape' => false],
            ['start' => '/*', 'end' => '*/', 'escape' => false],
        ],
        'identifier_quoting' => ['start' => '"', 'end' => '"','escape' => '"'],
        'string_quoting'     => [
            'start'          => "'",
            'end'            => "'",
            'escape'         => false,
            'escape_pattern' => false,
        ],
        'wildcards'             => ['%', '_'],
        'varchar_max_length'    => 255,
        'sql_file_delimiter'    => ";\n",
        'max_identifier_length' => 64,
    ];

    protected array $serverInfo = [];

    protected array $options = [];

    /**
     * @var array $supportedDrivers         an array containing all supported drivers
     */
    private static array $supportedDrivers = [
        'Mysql',
        'Pgsql',
        'Sqlite',
    ];

    protected int $count = 0;

    /**
     * @var array $usedNames                 array of foreign key names that have been used
     */
    protected array $usedNames = [
        'foreign_keys' => [],
        'indexes'      => []
    ];

    /**
     * @var ?Manager $parent   the parent of this component
     */
    protected $parent;

    public array $exported;

    /**
     * the constructor
     *
     * @param Manager $manager the manager object
     * @param PDO|array $adapter database driver
     */
    public function __construct(Manager $manager, PDO|array $adapter)
    {
        if ($adapter instanceof PDO) {
            $this->dbh = $adapter;
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } elseif (is_array($adapter)) {
            $this->options['dsn']      = $adapter['dsn'];
            $this->options['username'] = $adapter['user'];
            $this->options['password'] = $adapter['pass'];

            $this->options['other'] = [];
            if (isset($adapter['other'])) {
                $this->options['other'] = [PDO::ATTR_PERSISTENT => $adapter['persistent']];
            }
        }

        $this->setParent($manager);

        $this->getListener()->onOpen($this);
    }

    /**
     * Check wherther the connection to the database has been made yet
     */
    public function isConnected(): bool
    {
        return $this->dbh !== null;
    }

    /**
     * Get array of all options
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Retrieves option
     */
    public function getOption(string $option): mixed
    {
        return $this->options[$option] ?? null;
    }

    /**
     * Set option value
     *
     * @template T
     * @param  string $option
     * @param  mixed  $value
     * @return mixed
     * @phpstan-param T $value
     * @phpstan-return T
     * @throws Connection\Exception
     */
    public function setOption(string $option, mixed $value): mixed
    {
        return $this->options[$option] = $value;
    }

    /**
     * retrieves a database connection attribute
     */
    public function getAttribute(int $attribute): mixed
    {
        if ($this->isConnected()) {
            try {
                return $this->getDbh()->getAttribute($attribute);
            } catch (\Throwable $e) {
                throw new Connection\Exception("Attribute $attribute not found.", previous: $e);
            }
        } else {
            if (!isset($this->pendingAttributes[$attribute])) {
                $this->getDbh()->getAttribute($attribute);
            }

            return $this->pendingAttributes[$attribute];
        }
    }

    /**
     * returns an array of available PDO drivers
     */
    public static function getAvailableDrivers(): array
    {
        return PDO::getAvailableDrivers();
    }

    /**
     * Returns an array of supported drivers by Doctrine
     */
    public static function getSupportedDrivers(): array
    {
        return self::$supportedDrivers;
    }

    /**
     * sets an attribute
     *
     * @todo why check for >= 100? has this any special meaning when creating
     * attributes?
     *
     * @return $this
     */
    public function setAttribute(int $attribute, mixed $value): self
    {
        if ($this->isConnected()) {
            $this->getDbh()->setAttribute($attribute, $value);
        } else {
            $this->pendingAttributes[$attribute] = $value;
        }
        return $this;
    }

    /**
     * returns the name of this driver
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the connection
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the name of the instance driver
     */
    public function getDriverName(): string
    {
        return $this->driverName;
    }

    /**
     * lazy loads given module and returns it
     *
     * @see    DataDict
     * @see    Expression\Driver
     * @see    Export
     * @see    Transaction
     * @see    Connection::$modules       all availible modules
     * @param  string $name the name of the module to get
     * @throws Connection\Exception    if trying to get an unknown module
     * @return mixed       connection module
     */
    public function __get(string $name): mixed
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        if (!isset($this->modules[$name])) {
            throw new Connection\Exception('Unknown module / property ' . $name);
        }
        if ($this->modules[$name] === false) {
            switch ($name) {
                case 'unitOfWork':
                    $this->modules[$name] = new Connection\UnitOfWork($this);
                    break;
                case 'formatter':
                    $this->modules[$name] = new Formatter($this);
                    break;
                default:
                    $class                = __NAMESPACE__ . '\\' . ucwords($name) . '\\' . $this->getDriverName();
                    $this->modules[$name] = new $class($this);
            }
        }

        return $this->modules[$name];
    }

    /**
     * returns the manager that created this connection
     */
    public function getManager(): Manager
    {
        return $this->getParent();
    }

    /**
     * returns the database handler of which this connection uses
     */
    public function getDbh(): PDO
    {
        if ($this->dbh === null) {
            $this->connect();
            assert($this->dbh !== null); // @phpstan-ignore-line
        }
        return $this->dbh;
    }

    /**
     * connects into database
     * @throws Connection\Exception
     */
    public function connect(): bool
    {
        if ($this->dbh !== null) {
            return false;
        }

        $event = new Event($this, Event::CONN_CONNECT);

        $this->getListener()->preConnect($event);

        try {
            $this->dbh = new PDO(
                $this->options['dsn'],
                $this->options['username'],
                (!$this->options['password'] ? '' : $this->options['password']),
                $this->options['other']
            );
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Connection\Exception("PDO Connection Error: {$e->getMessage()}", previous: $e);
        }

        // attach the pending attributes to adapter
        foreach ($this->pendingAttributes as $attr => $value) {
            if ($attr == PDO::ATTR_DRIVER_NAME) {
                continue;
            }
            $this->dbh->setAttribute($attr, $value);
        }

        $this->getListener()->postConnect($event);
        return true;
    }

    public function incrementQueryCount(): void
    {
        $this->count++;
    }

    /**
     * converts given driver name
     */
    public function driverName(string $name): void
    {
    }

    /**
     * @param  string $feature the name of the feature
     * @return bool whether or not this drivers supports given feature
     */
    public function supports(string $feature): bool
    {
        return (isset($this->supported[$feature])
                  && ($this->supported[$feature] === 'emulated'
                   || $this->supported[$feature]));
    }

    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL and SQLIte implement it natively, this type of
     * query isemulated through this method for other DBMS using standard types
     * of queries inside a transaction to assure the atomicity of the operation.
     *
     *
     *
     *
     *
     *                          The values of the array are values to be assigned to the specified field.
     *
     *
     *
     *                          the uniqueness of a row will be determined according to
     *                          the provided key fields
     *
     *                          this method will fail if no key fields are specified
     *
     * @param Table $table  name of the table on which the REPLACE query will
     *                               be executed.
     * @param array          $fields an associative array that describes the fields and the
     *                               values that will be inserted or updated in the
     *                               specified table. The indexes of the array are the
     *                               names of all the fields of the table.
     * @param array          $keys   an array containing all key fields (primary key fields
     *                               or unique index fields) for this table
     *
     * @throws Connection\Exception        if this driver doesn't support replace
     * @throws Connection\Exception        if some of the key values was null
     * @throws Connection\Exception        if there were no key fields
     * @throws PDOException                         if something fails at PDO level
     * @return int                              number of rows affected
     */
    public function replace(Table $table, array $fields, array $keys): int
    {
        if (empty($keys)) {
            throw new Connection\Exception('Not specified which fields are keys');
        }
        $identifier = (array) $table->getIdentifier();
        $condition  = [];

        foreach ($fields as $fieldName => $value) {
            if (in_array($fieldName, $keys)) {
                if ($value !== null) {
                    $condition[]       = $table->getColumnName($fieldName) . ' = ?';
                    $conditionValues[] = $value;
                }
            }
        }

        $affectedRows = 0;
        if (!empty($condition) && !empty($conditionValues)) {
            $query = 'DELETE FROM ' . $this->quoteIdentifier($table->getTableName())
                    . ' WHERE ' . implode(' AND ', $condition);

            $affectedRows = $this->exec($query, $conditionValues);
        }

        $this->insert($table, $fields);

        $affectedRows++;

        return $affectedRows;
    }

    /**
     * deletes table row(s) matching the specified identifier
     *
     * @throws Connection\Exception    if something went wrong at the database level
     * @param  Table $table      The table to delete data from
     * @param  array          $identifier An associateve array containing identifier column-value pairs.
     * @return int              The number of affected rows
     */
    public function delete(Table $table, array $identifier): int
    {
        $tmp = [];

        foreach (array_keys($identifier) as $id) {
            $tmp[] = $this->quoteIdentifier($table->getColumnName($id)) . ' = ?';
        }

        $query = 'DELETE FROM '
               . $this->quoteIdentifier($table->getTableName())
               . ' WHERE ' . implode(' AND ', $tmp);

        return $this->exec($query, array_values($identifier));
    }

    /**
     * Updates table row(s) with specified data.
     *
     * @throws Connection\Exception    if something went wrong at the database level
     * @param Table $table  The table to insert data into
     * @param array $fields An associative array containing column-value pairs.
     *              Values can be strings or Expression
     *              instances.
     * @return int|null the number of affected rows. bool false if empty value array was given,
     */
    public function update(Table $table, array $fields, array $identifier): ?int
    {
        if (empty($fields)) {
            return null;
        }

        $set = [];
        foreach ($fields as $fieldName => $value) {
            if ($value instanceof Expression) {
                $set[] = $this->quoteIdentifier($table->getColumnName($fieldName)) . ' = ' . $value->getSql();
                unset($fields[$fieldName]);
            } else {
                $set[] = $this->quoteIdentifier($table->getColumnName($fieldName)) . ' = ?';
            }
        }

        $params = array_merge(array_values($fields), array_values($identifier));

        $sql = 'UPDATE ' . $this->quoteIdentifier($table->getTableName())
              . ' SET ' . implode(', ', $set)
              . ' WHERE ' . implode(' = ? AND ', $this->quoteMultipleIdentifier($table->getIdentifierColumnNames()))
              . ' = ?';

        return $this->exec($sql, $params);
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param  Table $table  The table to insert data into.
     * @param  array          $fields An associative array containing column-value pairs.
     *                                Values can be strings or Expression
     *                                instances.
     * @return int                  the number of affected rows. bool false if empty value array was given,
     */
    public function insert(Table $table, array $fields): int
    {
        $tableName = $table->getTableName();

        // column names are specified as array keys
        $cols = [];
        // the query VALUES will contain either expresions (eg 'NOW()') or ?
        $a = [];
        foreach ($fields as $fieldName => $value) {
            $cols[] = $this->quoteIdentifier($table->getColumnName($fieldName));
            if ($value instanceof Expression) {
                $a[] = $value->getSql();
                unset($fields[$fieldName]);
            } else {
                $a[] = '?';
            }
        }

        // build the statement
        $query = 'INSERT INTO ' . $this->quoteIdentifier($tableName)
                . ' (' . implode(', ', $cols) . ')'
                . ' VALUES (' . implode(', ', $a) . ')';

        return $this->exec($query, array_values($fields));
    }

    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mysql
     *   + mysqli
     *   + pgsql
     *   + sqlite
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str         identifier name to be quoted
     * @param bool   $checkOption check the 'quote_identifier' option
     *
     * @return string               quoted identifier string
     */
    public function quoteIdentifier(string $str, bool $checkOption = true): string
    {
        // quick fix for the identifiers that contain a dot
        if (strpos($str, '.')) {
            $e = explode('.', $str);

            return $this->formatter->quoteIdentifier($e[0], $checkOption) . '.'
                 . $this->formatter->quoteIdentifier($e[1], $checkOption);
        }
        return $this->formatter->quoteIdentifier($str, $checkOption);
    }

    /**
     * Quotes multiple identifier strings
     *
     * @param array $arr         identifiers array to be quoted
     * @param bool  $checkOption check the 'quote_identifier' option
     */
    public function quoteMultipleIdentifier(array $arr, bool $checkOption = true): array
    {
        foreach ($arr as $k => $v) {
            $arr[$k] = $this->quoteIdentifier($v, $checkOption);
        }

        return $arr;
    }

    /**
     * some drivers need the bool values to be converted into ints
     * when using DQL API
     *
     * This method takes care of that conversion
     *
     * @param array|string|bool|int|float|null $item
     *
     * @return mixed[]|bool|int|float|null
     */
    public function convertBooleans(array|string|bool|int|float|null $item): array|string|bool|int|float|null
    {
        return $this->formatter->convertBooleans($item);
    }

    /**
     * quotes given input parameter
     *
     * @param mixed  $input parameter to be quoted
     */
    public function quote(mixed $input, ?string $type = null): ?string
    {
        return $this->formatter->quote($input, $type);
    }

    /**
     * Set the date/time format for the current connection
     *
     * @param string $format time format
     */
    public function setDateFormat(string $format = null): void
    {
    }

    /**
     * @param  string $statement sql query to be executed
     * @param  array  $params    prepared statement params
     * @return array[]
     * @phpstan-return array<string, mixed>[]
     */
    public function fetchAll(string $statement, array $params = []): array
    {
        return $this->execute($statement, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param  string $statement sql query to be executed
     * @param  array  $params    prepared statement params
     * @param  int    $colnum    0-indexed column number to retrieve
     * @return mixed
     */
    public function fetchOne(string $statement, array $params = [], int $colnum = 0): mixed
    {
        return $this->execute($statement, $params)->fetchColumn($colnum);
    }

    /**
     * @param  string $statement sql query to be executed
     * @param  array  $params    prepared statement params
     * @phpstan-return ?array<string, mixed>
     */
    public function fetchRow(string $statement, array $params = []): ?array
    {
        $row = $this->execute($statement, $params)->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * @param  string $statement sql query to be executed
     * @param  array  $params    prepared statement params
     * @phpstan-return array<int, mixed>
     */
    public function fetchArray(string $statement, array $params = []): array
    {
        return $this->execute($statement, $params)->fetch(PDO::FETCH_NUM);
    }

    /**
     * @param  string $statement sql query to be executed
     * @param  array  $params    prepared statement params
     * @param  int    $colnum    0-indexed column number to retrieve
     * @phpstan-return array<int, mixed>
     */
    public function fetchColumn(string $statement, array $params = [], int $colnum = 0): array
    {
        return $this->execute($statement, $params)->fetchAll(PDO::FETCH_COLUMN, $colnum);
    }

    /**
     * @param  string $statement sql query to be executed
     * @param  array  $params    prepared statement params
     * @return array[]
     * @phpstan-return array<string, mixed>[]
     */
    public function fetchAssoc(string $statement, array $params = []): array
    {
        return $this->fetchAll($statement, $params);
    }

    /**
     * @param  string $statement sql query to be executed
     * @param  array  $params    prepared statement params
     * @return array[]
     * @phpstan-return array<string|int, mixed>[]
     */
    public function fetchBoth(string $statement, array $params = []): array
    {
        return $this->execute($statement, $params)->fetchAll(PDO::FETCH_BOTH);
    }

    /**
     * queries the database using Doctrine Query Language
     * returns a collection of Record objects
     *
     * <code>
     * $users = $conn->query('SELECT u.* FROM User u');
     *
     * $users = $conn->query('SELECT u.* FROM User u WHERE u.name LIKE ?', array('someone'));
     * </code>
     *
     * @param  string $query         DQL query
     * @param  array  $params        query parameters
     * @param  HydrationMode    $hydrationMode HydrationMode::Array or HydrationMode::Record
     * @see    Query
     * @return Collection      Collection of Record objects
     */
    public function query(string $query, array $params = [], ?HydrationMode $hydrationMode = null): Collection
    {
        $parser = Query::create($this);
        $res    = $parser->query($query, $params, $hydrationMode);
        $parser->free();

        return $res;
    }

    public function prepare(string $statement): Connection\Statement
    {
        $aliases = [];
        $statement = $this->rewriteQuery($statement, $aliases);

        $this->connect();

        try {
            $event = new Event($this, Event::CONN_PREPARE, $statement);
            $this->getListener()->prePrepare($event);
            /** @var Connection\Statement|PDOStatement */
            $stmt = $this->getDbh()->prepare($statement);
            $this->getListener()->postPrepare($event);
            if ($stmt instanceof PDOStatement) {
                $stmt = new Connection\Statement($this, $stmt, $aliases);
            } else {
                $stmt->setBindAliases($aliases);
            }
            return $stmt;
        } catch (PDOException $e) {
            $this->rethrowException($e, $this, $statement);
        }
    }

    /**
     * queries the database using Doctrine Query Language and returns
     * the first record found
     *
     * <code>
     * $user = $conn->queryOne('SELECT u.* FROM User u WHERE u.id = ?', array(1));
     *
     * $user = $conn->queryOne('SELECT u.* FROM User u WHERE u.name LIKE ? AND u.password = ?',
     *         array('someone', 'password')
     *         );
     * </code>
     *
     * @param  string $query  DQL query
     * @param  array  $params query parameters
     * @see    Query
     * @return Record|null    Record object on success,
     *                                  bool false on failure
     */
    public function queryOne(string $query, array $params = []): ?Record
    {
        $parser = Query::create();

        $coll = $parser->query($query, $params);
        if (!$coll->contains(0)) {
            return null;
        }
        return $coll[0];
    }

    /**
     * queries the database with limit and offset
     * added to the query and returns a Connection\Statement object
     */
    public function select(string $query, int $limit = 0, int $offset = 0): Connection\Statement
    {
        if ($limit > 0 || $offset > 0) {
            $query = $this->modifyLimitQuery($query, $limit, $offset);
        }
        return $this->execute($query);
    }

    /**
     * @param string $query  sql query
     * @param array  $params query parameters
     */
    public function standaloneQuery(string $query, array $params = []): Connection\Statement
    {
        return $this->execute($query, $params);
    }

    /**
     * @param string $query  sql query
     * @param array  $params query parameters
     */
    public function execute(string $query, array $params = []): Connection\Statement
    {
        $this->connect();

        try {
            if (!empty($params)) {
                $stmt = $this->prepare($query);
                $stmt->execute($params);
                return $stmt;
            } else {
                $event = new Event($this, Event::CONN_QUERY, $query, $params);
                $this->getListener()->preQuery($event);
                /** @var Connection\Statement|PDOStatement */
                $stmt = $this->getDbh()->query($query);
                $this->incrementQueryCount();
                $this->getListener()->postQuery($event);
                if ($stmt instanceof PDOStatement) {
                    $stmt = new Connection\Statement($this, $stmt);
                }
                return $stmt;
            }
        } catch (PDOException $e) {
            $this->rethrowException($e, $this, $query);
        }
    }

    /**
     * @param string $query  sql query
     * @param array  $params query parameters
     */
    public function exec(string $query, array $params = []): int
    {
        $this->connect();

        try {
            if (!empty($params)) {
                $stmt = $this->prepare($query);
                $stmt->execute($params);

                return $stmt->rowCount();
            } else {
                $event = new Event($this, Event::CONN_EXEC, $query, $params);
                $this->getListener()->preExec($event);
                /** @var int */
                $count = $this->getDbh()->exec($query);
                $this->incrementQueryCount();
                $this->getListener()->postExec($event);
                return $count;
            }
        } catch (PDOException $e) {
            $this->rethrowException($e, $this, $query);
        }
    }

    /**
     * @throws Connection\Exception
     * @return never
     */
    public function rethrowException(PDOException $e, mixed $invoker, ?string $query = null): void
    {
        $event = new Event($this, Event::CONN_ERROR);

        $this->getListener()->preError($event);

        /** @var class-string $name */
        $name = 'Doctrine1\\Connection\\' . $this->driverName . '\\Exception';

        $message = $e->getMessage();
        if ($query) {
            $message .= sprintf('. Failing Query: "%s"', $query);
        }

        /** @var Connection\Exception $exc */
        $exc = new $name($message, (int) $e->getCode());
        $e->errorInfo ??= [null, null, null, null];
        $exc->processErrorInfo($e->errorInfo);
        throw $exc;
    }

    /**
     * whether or not this connection has table $name initialized
     */
    public function hasTable(string $name): bool
    {
        return isset($this->tables[$name]);
    }

    /** @phpstan-return class-string<Table> */
    public function getTableClassName(string $name): string
    {
        $namespace = '';
        if (class_exists($name)) {
            if (($pos = strrpos($name, '\\')) !== false) {
                $namespace = substr($name, 0, $pos);
                $name = substr($name, $pos + 1);
            }
        } else {
            $namespace = $this->getModelNamespace();
        }

        $class = sprintf($this->getTableClassFormat(), $name);
        $class = trim(trim($namespace, '\\') . "\\$class", '\\');

        if (!class_exists($class) || !in_array(Table::class, class_parents($class) ?: [])) {
            $class = $this->getTableClass();
        }

        /** @phpstan-var class-string<Table> $class */
        return $class;
    }

    /**
     * returns a table object for given component name
     */
    public function getTable(string $name): Table
    {
        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }

        $class = $this->getTableClassName($name);
        return new $class($name, $this, true);
    }

    /**
     * returns an array of all initialized tables
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * returns an iterator that iterators through all
     * initialized table objects
     *
     * <code>
     * foreach ($conn as $index => $table) {
     *      print $table;  // get a string representation of each table object
     * }
     * </code>
     *
     * @return ArrayIterator SPL ArrayIterator object
     * @phpstan-return ArrayIterator<Table>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->tables);
    }

    /**
     * returns the count of initialized table objects
     *
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * adds a Table object into connection registry
     *
     * @param  Table $table a Table object to be added into registry
     */
    public function addTable(Table $table): bool
    {
        $name = $table->getComponentName();

        if (isset($this->tables[$name])) {
            return false;
        }
        $this->tables[$name] = $table;
        return true;
    }

    /**
     * creates a record
     *
     * @param  string $name component name
     * @return Record      Record object
     */
    public function create(string $name): Record
    {
        return $this->getTable($name)->create();
    }

    /**
     * Creates a new Query object that operates on this connection.
     */
    public function createQuery(): Query
    {
        return Query::create();
    }

    /**
     * saves all the records from all tables
     * this operation is isolated using a transaction
     *
     * @throws PDOException         if something went wrong at database level
     */
    public function flush(): void
    {
        $savepoint = $this->beginInternalTransaction();
        try {
            $this->unitOfWork->saveAll();
            $savepoint->commit();
        } catch (Throwable $e) {
            $savepoint->rollback();
            throw $e;
        }
    }

    /**
     * clears all repositories
     */
    public function clear(): void
    {
        foreach ($this->tables as $k => $table) {
            $repo = $table->getRepository();
            if ($repo !== null) {
                $repo->evictAll();
            }
            $table->clear();
        }
    }

    /**
     * evicts all tables
     */
    public function evictTables(): void
    {
        $this->tables   = [];
        $this->exported = [];
    }

    /**
     * closes the connection
     */
    public function close(): void
    {
        $event = new Event($this, Event::CONN_CLOSE);

        $this->getListener()->preClose($event);

        $this->clear();

        $this->dbh = null;

        $this->getListener()->postClose($event);
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the database handle
     */
    public function errorCode(): int|null|string
    {
        return $this->getDbh()->errorCode();
    }

    /**
     * Fetch extended error information associated with the last operation on the database handle
     */
    public function errorInfo(): array|string
    {
        return $this->getDbh()->errorInfo();
    }

    /**
     * @throws Exception
     */
    public function getResultCacheDriver(): CacheInterface
    {
        $resultCache = $this->getResultCache();
        if (!$resultCache) {
            throw new Exception('Result Cache driver not initialized.');
        }
        return $resultCache;
    }

    /**
     * @throws Exception
     */
    public function getQueryCacheDriver(): CacheInterface
    {
        $queryCache = $this->getQueryCache();
        if (!$queryCache) {
            throw new Exception('Query Cache driver not initialized.');
        }
        return $queryCache;
    }

    /**
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers,
     * because the underlying database may not even support the notion of auto-increment fields or sequences.
     *
     * @param string|null $table name of the table into which a new row was inserted
     * @param string|null $field name of the field into which a new row was inserted
     */
    public function lastInsertId(?string $table = null, ?string $field = null): int|string
    {
        return $this->sequence->lastInsertId($table, $field);
    }

    /**
     * Start a transaction or set a savepoint.
     *
     * if trying to set a savepoint and there is no active transaction
     * a new transaction is being started
     *
     * Listeners: onPreTransactionBegin, onTransactionBegin
     *
     * @param  string|null $savepoint name of a savepoint to set
     * @throws Transaction\Exception   if the transaction fails at database level
     */
    public function beginTransaction(?string $savepoint = null): SavePoint
    {
        return $this->transaction->beginTransaction($savepoint);
    }

    public function beginInternalTransaction(?string $savepoint = null): SavePoint
    {
        return $this->transaction->beginTransaction($savepoint, true);
    }

    /**
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail.
     *
     * Listeners: onPreTransactionCommit, onTransactionCommit
     *
     * @param  string|SavePoint|null $savepoint savepoint to release
     * @throws Transaction\Exception   if the transaction fails at PDO level
     * @throws Validator\Exception     if the transaction fails due to record validations
     */
    public function commit(string|SavePoint|null $savepoint = null): void
    {
        $this->transaction->commit($savepoint);
    }

    /**
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * this method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @param  string|SavePoint|null $savepoint savepoint to rollback to
     * @throws Transaction\Exception   if the rollback operation fails at database level
     */
    public function rollback(string|SavePoint|null $savepoint = null, bool $all = false): void
    {
        $this->transaction->rollback($savepoint, $all);
    }

    /**
     * Issue create database command for this instance of Connection
     * @throws Connection\Exception
     */
    public function createDatabase(): void
    {
        if (!$dsn = $this->getOption('dsn')) {
            throw new Connection\Exception('You must create your Connection by using a valid Doctrine style dsn in order to use the create/drop database functionality');
        }

        // Parse pdo dsn so we are aware of the connection information parts
        $info = $this->getManager()->parsePdoDsn($dsn);

        if (!isset($info['dbname'])) {
            throw new Connection\Exception('The connection dsn must specify a dbname in order to use the create/drop database functionality');
        }

        // Get the temporary connection to issue the create database command
        $tmpConnection = $this->getTmpConnection($info);

        try {
            $tmpConnection->export->createDatabase($info['dbname']);
        } finally {
            // Close the temporary connection used to issue the drop database command
            $this->getManager()->closeConnection($tmpConnection);
        }
    }

    /**
     * Issue drop database command for this instance of Connection
     * @throws Connection\Exception
     */
    public function dropDatabase(): void
    {
        if (!$dsn = $this->getOption('dsn')) {
            throw new Connection\Exception('You must create your Connection by using a valid Doctrine style dsn in order to use the create/drop database functionality');
        }

        // Parse pdo dsn so we are aware of the connection information parts
        $info = $this->getManager()->parsePdoDsn($dsn);

        if (!isset($info['dbname'])) {
            throw new Connection\Exception('The connection dsn must specify a dbname in order to use the create/drop database functionality');
        }

        // Get the temporary connection to issue the drop database command
        $tmpConnection = $this->getTmpConnection($info);

        try {
            $tmpConnection->export->dropDatabase($info['dbname']);
        } finally {
            // Close the temporary connection used to issue the drop database command
            $this->getManager()->closeConnection($tmpConnection);
        }
    }

    /**
     * Create a temporary connection to the database with the user credentials.
     * This is so the user can make a connection to a db server. Some dbms allow
     * connections with no database, but some do not. In that case we have a table
     * which is always guaranteed to exist. Mysql: 'mysql', PostgreSQL: 'postgres', etc.
     * This value is set in the Export_{DRIVER} classes if required
     */
    public function getTmpConnection(array $info): Connection
    {
        $pdoDsn = $info['scheme'] . ':';

        if ($info['unix_socket']) {
            $pdoDsn .= 'unix_socket=' . $info['unix_socket'] . ';';
        }

        $pdoDsn .= 'host=' . $info['host'];

        if ($info['port']) {
            $pdoDsn .= ';port=' . $info['port'];
        }

        if (isset($this->export->tmpConnectionDatabase) && $this->export->tmpConnectionDatabase) {
            $pdoDsn .= ';dbname=' . $this->export->tmpConnectionDatabase;
        }

        $username = $this->getOption('username');
        $password = $this->getOption('password');

        $conn = $this->getManager()->openConnection([$pdoDsn, $username, $password], 'doctrine_tmp_connection', false);
        $conn->setOption('username', $username);
        $conn->setOption('password', $password);

        return $conn;
    }

    /**
     * Some dbms require specific functionality for this. Check the other connection adapters for examples
     */
    public function modifyLimitQuery(string $query, ?int $limit = null, ?int $offset = null, bool $isManip = false): string
    {
        $limit  = (int) $limit;
        $offset = (int) $offset;

        if (!$limit && !$offset) {
            return $query;
        }

        if (!$limit) {
            $limit = 999999999999;
        }

        $query .= " LIMIT $limit";

        if ($offset) {
            $query .= " OFFSET $offset";
        }

        return $query;
    }

    /**
     * Creates dbms specific LIMIT/OFFSET SQL for the subqueries that are used in the
     * context of the limit-subquery algorithm.
     */
    public function modifyLimitSubquery(Table $rootTable, string $query, ?int $limit = null, ?int $offset = null, bool $isManip = false): string
    {
        return $this->modifyLimitQuery($query, $limit, $offset, $isManip);
    }

    /**
     * Serialize. Remove database connection(pdo) since it cannot be serialized
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        $vars['dbh'] = null;
        return $vars;
    }

    /**
     * Unserialize. Recreate connection from serialized content
     */
    public function __unserialize(array $serialized): void
    {
        foreach ($serialized as $name => $values) {
            $this->$name = $values;
        }
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }

    /**
     * Get/generate a unique foreign key name for a relationship
     *
     * @param  Relation $relation Relation object to generate the foreign key name for
     */
    public function generateUniqueRelationForeignKeyName(Relation $relation): string
    {
        $parts = [
            $relation->getLocalTableName(),
            $relation->getLocalColumnName(),
            $relation->getForeignTableName(),
            $relation->getForeignColumnName(),
        ];
        $key    = implode('_', array_merge($parts, [$relation['onDelete']], [$relation['onUpdate']]));
        $format = $this->getForeignKeyNameFormat();

        return $this->generateUniqueName('foreign_keys', $parts, $key, $format, $this->getMaxIdentifierLength());
    }

    /**
     * Get/generate unique index name for a table name and set of fields
     *
     * @param  string $tableName The name of the table the index exists
     * @param  string $fields    The fields that makes up the index
     * @return string $indexName    The name of the generated index
     */
    public function generateUniqueIndexName(string $tableName, string $fields): string
    {
        $fields = (array) $fields;
        $parts  = [$tableName];
        $parts  = array_merge($parts, $fields);
        $key    = implode('_', $parts);
        $format = $this->getIndexNameFormat();

        return $this->generateUniqueName('indexes', $parts, $key, $format, $this->getMaxIdentifierLength());
    }

    /**
     * @param  string $type
     * @param  array  $parts
     * @param  string $key
     * @param  string $format
     * @param  int|null    $maxLength
     * @return string
     */
    protected function generateUniqueName(string $type, array $parts, string $key, string $format = '%s', ?int $maxLength = null): string
    {
        if (isset($this->usedNames[$type][$key])) {
            return $this->usedNames[$type][$key];
        }
        if ($maxLength === null) {
            $maxLength = $this->properties['max_identifier_length'];
        }

        $generated = implode('_', $parts);

        // If the final length is greater than 64 we need to create an abbreviated fk name
        if (strlen(sprintf($format, $generated)) > $maxLength) {
            $generated = '';

            foreach ($parts as $part) {
                $generated .= $part[0];
            }

            $name = $generated;
        } else {
            $name = $generated;
        }

        while (in_array($name, $this->usedNames[$type])) {
            $e   = explode('_', $name);
            $end = end($e);

            if (is_numeric($end)) {
                unset($e[count($e) - 1]);
                $fkName = implode('_', $e);
                $name   = $fkName . '_' . ++$end;
            } else {
                $name .= '_1';
            }
        }

        $this->usedNames[$type][$key] = $name;

        return $name;
    }

    /**
     * Rewrites the query generating aliases for bind parameters that are referenced multiple times
     * @phpstan-param array<string, string> $aliases
     */
    public function rewriteQuery(string $query, array &$aliases): string
    {
        $tokens = [];

        if (preg_match_all('/:\w+/', $query, $m)) {
            /** @var array<string, int> */
            $tokens = array_count_values($m[0]);
        }

        foreach ($tokens as $token => $count) {
            for ($x = $count; $x > 1; --$x) {
                $alias = substr($token, 0, 1)."_alias{$x}_".substr($token, 1);
                $aliases[$alias] = $token;

                $ptoken = preg_quote($token);
                $query = preg_replace("#$ptoken(\W)#", "$alias\\1", $query, 1) ?? $query;
            }
        }

        return $query;
    }
}
