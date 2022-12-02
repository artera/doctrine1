<?php

namespace Doctrine1;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Throwable;

/**
 * Migration
 *
 * this class represents a database view
 */
class Migration
{
    /**
     * @var string
     */
    protected $migrationTableName = 'migration_version';

    /**
     * @var bool
     */
    protected $migrationTableCreated = false;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $migrationClassesDirectory = '';

    /**
     * @var string[]
     * @phpstan-var class-string<Migration\Base>[]
     */
    protected array $migrationClasses = [];

    /**
     * @var ReflectionClass
     */
    protected $reflectionClass;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var Migration\Process
     */
    protected $process;

    /**
     * @phpstan-var array<string, class-string<Migration\Base>[]>
     */
    protected static array $migrationClassesForDirectories = [];

    /**
     * Specify the path to the directory with the migration classes.
     * The classes will be loaded and the migration table will be created if it
     * does not already exist
     *
     * @param  string $directory  The path to your migrations directory
     * @param  mixed  $connection The connection name or instance to use for this migration
     * @return void
     */
    public function __construct($directory = null, $connection = null)
    {
        $this->reflectionClass = new ReflectionClass(Migration\Base::class);

        if ($connection === null) {
            $this->connection = Manager::connection();
        } else {
            if (is_string($connection)) {
                $this->connection = Manager::getInstance()
                    ->getConnection($connection);
            } else {
                $this->connection = $connection;
            }
        }

        $this->process = new Migration\Process($this);

        if ($directory !== null) {
            $this->migrationClassesDirectory = $directory;

            $this->loadMigrationClassesFromDirectory();
        }
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return void
     */
    public function setConnection(Connection $conn)
    {
        $this->connection = $conn;
    }

    /**
     * Get the migration classes directory
     *
     * @return string $migrationClassesDirectory
     */
    public function getMigrationClassesDirectory()
    {
        return $this->migrationClassesDirectory;
    }

    /**
     * Get the table name for storing the version number for this migration instance
     *
     * @return string $migrationTableName
     */
    public function getTableName()
    {
        return $this->migrationTableName;
    }

    /**
     * Set the table name for storing the version number for this migration instance
     *
     * @param  string $tableName
     * @return void
     */
    public function setTableName($tableName)
    {
        $this->migrationTableName = $tableName;
    }

    /**
     * Load migration classes from the passed directory. Any file found with a .php
     * extension will be passed to the loadMigrationClass()
     *
     * @param  string|string[]|null $directory Directory to load migration classes from
     * @return void
     */
    public function loadMigrationClassesFromDirectory($directory = null)
    {
        $directory = $directory ? $directory : $this->migrationClassesDirectory;

        $classesToLoad = [];
        $classes       = get_declared_classes();
        foreach ((array) $directory as $dir) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            if (isset(self::$migrationClassesForDirectories[$dir])) {
                foreach (self::$migrationClassesForDirectories[$dir] as $num => $className) {
                    $this->migrationClasses[$num] = $className;
                }
            }

            foreach ($it as $file) {
                $info = pathinfo($file->getFileName());
                if (isset($info['extension']) && $info['extension'] == 'php') {
                    include_once $file->getPathName();

                    $array     = array_diff(get_declared_classes(), $classes);
                    $className = end($array);

                    if ($className && is_subclass_of($className, Migration\Base::class)) {
                        $e         = explode('_', $file->getFileName());
                        $timestamp = $e[0];

                        $classesToLoad[$timestamp] = ['className' => $className, 'path' => $file->getPathName()];
                    }
                }
            }
        }
        ksort($classesToLoad, SORT_NUMERIC);
        foreach ($classesToLoad as $class) {
            $this->loadMigrationClass($class['className'], $class['path']);
        }
    }

    /**
     * Load the specified migration class name in to this migration instances queue of
     * migration classes to execute. It must be a child of Migration in order
     * to be loaded.
     *
     * @param  string $name
     * @phpstan-param class-string<Migration\Base> $name
     * @param  string $path
     * @return bool
     */
    public function loadMigrationClass(string $name, ?string $path = null): bool
    {
        $class = new ReflectionClass($name);

        while ($class->isSubclassOf($this->reflectionClass)) {
            $class = $class->getParentClass();
            if ($class === false) {
                return false;
            }
        }

        if (empty($this->migrationClasses)) {
            $classMigrationNum = 1;
        } else {
            $nums = array_keys($this->migrationClasses);
            $num = end($nums);
            $classMigrationNum = $num + 1;
        }

        $this->migrationClasses[$classMigrationNum] = $name;

        if ($path) {
            $dir = dirname($path);
            self::$migrationClassesForDirectories[$dir][$classMigrationNum] = $name;
        }

        return true;
    }

    /**
     * Get all the loaded migration classes. Array where key is the number/version
     * and the value is the class name.
     *
     * @return array $migrationClasses
     */
    public function getMigrationClasses()
    {
        return $this->migrationClasses;
    }

    /**
     * Set the current version of the database
     *
     * @param  integer $number
     * @return void
     */
    public function setCurrentVersion($number)
    {
        if ($this->hasMigrated()) {
            $this->connection->exec('UPDATE ' . $this->migrationTableName . " SET version = $number");
        } else {
            $this->connection->exec('INSERT INTO ' . $this->migrationTableName . " (version) VALUES ($number)");
        }
    }

    /**
     * Get the current version of the database
     *
     * @return integer $version
     */
    public function getCurrentVersion()
    {
        $this->createMigrationTable();

        $result = $this->connection->fetchColumn('SELECT version FROM ' . $this->migrationTableName);

        return isset($result[0]) ? $result[0] : 0;
    }

    /**
     * hReturns true/false for whether or not this database has been migrated in the past
     *
     * @return boolean $migrated
     */
    public function hasMigrated()
    {
        $this->createMigrationTable();

        $result = $this->connection->fetchColumn('SELECT version FROM ' . $this->migrationTableName);

        return isset($result[0]) ? true : false;
    }

    /**
     * Gets the latest possible version from the loaded migration classes
     *
     * @return integer $latestVersion
     */
    public function getLatestVersion()
    {
        $versions = array_keys($this->migrationClasses);
        rsort($versions);

        return isset($versions[0]) ? $versions[0] : 0;
    }

    /**
     * Get the next incremented version number based on the latest version number
     * using getLatestVersion()
     *
     * @return integer $nextVersion
     */
    public function getNextVersion()
    {
        return $this->getLatestVersion() + 1;
    }

    /**
     * Get the next incremented class version based on the loaded migration classes
     *
     * @return int $nextMigrationClassVersion
     */
    public function getNextMigrationClassVersion(): int
    {
        if (empty($this->migrationClasses)) {
            return 1;
        } else {
            $nums = array_keys($this->migrationClasses);
            return (int) end($nums) + 1;
        }
    }

    /**
     * Perform a migration process by specifying the migration number/version to
     * migrate to. It will automatically know whether you are migrating up or down
     * based on the current version of the database.
     *
     * @param int|null $to     Version to migrate to
     * @param boolean $dryRun Whether or not to run the migrate process as a dry run
     *
     * @return int|null $to Version number migrated to
     *
     * @throws Exception
     */
    public function migrate(?int $to = null, bool $dryRun = false): ?int
    {
        $this->clearErrors();

        $this->createMigrationTable();

        $this->connection->beginTransaction();

        // If nothing specified then lets assume we are migrating from
        // the current version to the latest version
        if ($to === null) {
            $to = $this->getLatestVersion();
        }

        try {
            $this->doMigrate($to);
        } catch (Throwable $e) {
            $this->addError($e);
        }

        if ($this->hasErrors()) {
            $this->connection->rollback();
            if ($dryRun) {
                return null;
            } else {
                $this->throwErrorsException();
            }
        } else {
            if ($dryRun) {
                $this->connection->rollback();
                /** @phpstan-ignore-next-line */
                return $this->hasErrors() ? null : $to;
            } else {
                $this->connection->commit();
                $this->setCurrentVersion($to);
                return $to;
            }
        }
        return null;
    }

    /**
     * Run the migration process but rollback at the very end. Returns true or
     * false for whether or not the migration can be ran
     */
    public function migrateDryRun(?int $to = null): ?int
    {
        return $this->migrate($to, true);
    }

    /**
     * Get the number of errors
     */
    public function getNumErrors(): int
    {
        return count($this->errors);
    }

    /**
     * Get all the error exceptions
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Clears the error exceptions
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Add an error to the stack. Excepts some type of Exception
     */
    public function addError(Throwable $e): void
    {
        $this->errors[] = $e;
    }

    /**
     * Whether or not the migration instance has errors
     */
    public function hasErrors(): bool
    {
        return $this->getNumErrors() > 0;
    }

    /**
     * Get instance of migration class for number/version specified
     *
     * @throws Migration\Exception $e
     */
    public function getMigrationClass(int $num): Migration\Base
    {
        if (isset($this->migrationClasses[$num])) {
            $className = $this->migrationClasses[$num];
            return new $className();
        }

        throw new Migration\Exception('Could not find migration class for migration step: ' . $num);
    }

    /**
     * Throw an exception with all the errors trigged during the migration
     *
     * @throws Migration\Exception $e
     */
    protected function throwErrorsException(): void
    {
        $messages = [];
        $num      = 0;
        foreach ($this->getErrors() as $error) {
            $num++;
            $messages[] = ' Error #' . $num . ' - ' . $error->getMessage() . "\n" . $error->getTraceAsString() . "\n";
        }

        $title   = $this->getNumErrors() . ' error(s) encountered during migration';
        $message = $title . "\n";
        $message .= str_repeat('=', strlen($title)) . "\n";
        $message .= implode("\n", $messages);

        throw new Migration\Exception($message);
    }

    /**
     * Do the actual migration process
     *
     * @throws Exception
     */
    protected function doMigrate(int $to): int
    {
        $from = $this->getCurrentVersion();

        if ($from == $to) {
            throw new Migration\Exception('Already at version # ' . $to);
        }

        $direction = $from > $to ? 'down' : 'up';

        if ($direction === 'up') {
            for ($i = $from + 1; $i <= $to; $i++) {
                $this->doMigrateStep($direction, $i);
            }
        } else {
            for ($i = $from; $i > $to; $i--) {
                $this->doMigrateStep($direction, $i);
            }
        }

        return $to;
    }

    /**
     * Perform a single migration step. Executes a single migration class and
     * processes the changes
     *
     * @param string $direction Direction to go, 'up' or 'down'
     * @phpstan-param 'up'|'down' $direction
     */
    protected function doMigrateStep(string $direction, int $num): void
    {
        try {
            $migration = $this->getMigrationClass($num);

            $method = 'pre' . $direction;
            $migration->$method();

            if (method_exists($migration, $direction)) {
                $migration->$direction();
            } elseif (method_exists($migration, 'migrate')) {
                $migration->migrate($direction);
            }

            if ($migration->getNumChanges() > 0) {
                $changes = $migration->getChanges();
                if ($direction == 'down' && method_exists($migration, 'migrate')) {
                    $changes = array_reverse($changes);
                }
                foreach ($changes as $value) {
                    list($type, $change) = $value;
                    $funcName            = 'process' . Inflector::classify($type);
                    if (method_exists($this->process, $funcName)) {
                        try {
                            $this->process->$funcName($change);
                        } catch (Throwable $e) {
                            $this->addError($e);
                        }
                    } else {
                        throw new Migration\Exception(sprintf('Invalid migration change type: %s', $type));
                    }
                }
            }

            $method = 'post' . $direction;
            $migration->$method();
        } catch (Throwable $e) {
            $this->addError($e);
        }
    }

    /**
     * Create the migration table and return true. If it already exists it will
     * silence the exception and return false
     *
     * @return boolean $created Whether or not the table was created. Exceptions
     *                          are silenced when table already exists
     */
    protected function createMigrationTable(): bool
    {
        if ($this->migrationTableCreated) {
            return true;
        }

        $this->migrationTableCreated = true;

        try {
            $this->connection->export->createTable($this->migrationTableName, ['version' => ['type' => 'integer', 'size' => 11]]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
