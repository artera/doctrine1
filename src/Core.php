<?php

namespace Doctrine1;

/**
 * The base core class of Doctrine
 */
class Core
{
    /**
     * ERROR CONSTANTS
     */
    public const ERR                     = -1;
    public const ERR_SYNTAX              = -2;
    public const ERR_CONSTRAINT          = -3;
    public const ERR_NOT_FOUND           = -4;
    public const ERR_ALREADY_EXISTS      = -5;
    public const ERR_UNSUPPORTED         = -6;
    public const ERR_MISMATCH            = -7;
    public const ERR_INVALID             = -8;
    public const ERR_NOT_CAPABLE         = -9;
    public const ERR_TRUNCATED           = -10;
    public const ERR_INVALID_NUMBER      = -11;
    public const ERR_INVALID_DATE        = -12;
    public const ERR_DIVZERO             = -13;
    public const ERR_NODBSELECTED        = -14;
    public const ERR_CANNOT_CREATE       = -15;
    public const ERR_CANNOT_DELETE       = -16;
    public const ERR_CANNOT_DROP         = -17;
    public const ERR_NOSUCHTABLE         = -18;
    public const ERR_NOSUCHFIELD         = -19;
    public const ERR_NEED_MORE_DATA      = -20;
    public const ERR_NOT_LOCKED          = -21;
    public const ERR_VALUE_COUNT_ON_ROW  = -22;
    public const ERR_INVALID_DSN         = -23;
    public const ERR_CONNECT_FAILED      = -24;
    public const ERR_NOSUCHDB            = -26;
    public const ERR_ACCESS_VIOLATION    = -27;
    public const ERR_CANNOT_REPLACE      = -28;
    public const ERR_CONSTRAINT_NOT_NULL = -29;
    public const ERR_DEADLOCK            = -30;
    public const ERR_CANNOT_ALTER        = -31;
    public const ERR_MANAGER             = -32;
    public const ERR_MANAGER_PARSE       = -33;
    public const ERR_LOADMODULE          = -34;
    public const ERR_INSUFFICIENT_DATA   = -35;
    public const ERR_CLASS_NAME          = -36;

    /**
     * PORTABILITY CONSTANTS
     */

    /**
     * Portability: turn off all portability features.
     */
    public const PORTABILITY_NONE = 0;

    /**
     * Portability: convert names of tables and fields to case defined in the
     * "field_case" option when using the query*(), fetch*() methods.
     */
    public const PORTABILITY_FIX_CASE = 1;

    /**
     * Portability: right trim the data output by query*() and fetch*().
     */
    public const PORTABILITY_RTRIM = 2;

    /**
     * Portability: force reporting the number of rows deleted.
     */
    public const PORTABILITY_DELETE_COUNT = 4;

    /**
     * Portability: convert empty values to null strings in data output by
     * query*() and fetch*().
     */
    public const PORTABILITY_EMPTY_TO_NULL = 8;

    /**
     * Portability: removes database/table qualifiers from associative indexes
     */
    public const PORTABILITY_FIX_ASSOC_FIELD_NAMES = 16;

    /**
     * Portability: makes Expression throw exception for unportable RDBMS expressions
     */
    public const PORTABILITY_EXPR = 32;

    /**
     * Portability: turn on all portability features.
     */
    public const PORTABILITY_ALL = 63;

    /**
     * EXPORT CONSTANTS
     */

    /**
     * EXPORT_NONE
     */
    public const EXPORT_NONE = 0;

    /**
     * EXPORT_TABLES
     */
    public const EXPORT_TABLES = 1;

    /**
     * EXPORT_CONSTRAINTS
     */
    public const EXPORT_CONSTRAINTS = 2;

    /**
     * EXPORT_PLUGINS
     */
    public const EXPORT_PLUGINS = 4;

    /**
     * EXPORT_ALL
     */
    public const EXPORT_ALL = 7;

    /**
     * VALIDATION CONSTANTS
     */
    public const VALIDATE_NONE = 0;

    /**
     * VALIDATE_LENGTHS
     */
    public const VALIDATE_LENGTHS = 1;

    /**
     * VALIDATE_TYPES
     */
    public const VALIDATE_TYPES = 2;

    /**
     * VALIDATE_CONSTRAINTS
     */
    public const VALIDATE_CONSTRAINTS = 4;

    /**
     * VALIDATE_ALL
     */
    public const VALIDATE_ALL = 7;

    /**
     * VALIDATE_USER
     */
    public const VALIDATE_USER = 8;

    /**
     * Path to Doctrine root
     */
    private static ?string $path = null;

    /**
     * Debug bool true/false option
     */
    private static bool $debug = false;

    /**
     * Array of all the loaded models and the path to each one for autoloading
     */
    private static array $loadedModelFiles = [];

    /**
     * Path to the models directory
     */
    private static ?string $modelsDirectory = null;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('Doctrine is static class. No instances can be created.');
    }

    /**
     * Returns an array of all the loaded models and the path where each of them exists
     */
    public static function getLoadedModelFiles(): array
    {
        return self::$loadedModelFiles;
    }

    /**
     * Turn on/off the debugging setting
     */
    public static function debug(?bool $bool = null): bool
    {
        if ($bool !== null) {
            self::$debug = $bool;
        }
        return self::$debug;
    }

    /**
     * Set the path to your core Doctrine libraries
     *
     * @param string $path The path to your Doctrine libraries
     */
    public static function setPath(string $path): void
    {
        self::$path = $path;
    }

    /**
     * Get the root path to Doctrine
     */
    public static function getPath(): ?string
    {
        if (!self::$path) {
            self::$path = realpath(dirname(__FILE__) . '/..') ?: null;
        }
        return self::$path;
    }

    /**
     * Load an individual model name and path in to the model loading registry
     */
    public static function loadModel(string $className, ?string $path = null): void
    {
        self::$loadedModelFiles[$className] = $path;
    }

    /**
     * Set the directory where your models are located for PEAR style
     * naming convention autoloading.
     */
    public static function setModelsDirectory(?string $directory): void
    {
        self::$modelsDirectory = $directory;
    }

    /**
     * Get the directory where your models are located for PEAR style naming
     * convention autoloading
     */
    public static function getModelsDirectory(): ?string
    {
        return self::$modelsDirectory;
    }

    /**
     * Recursively load all models from a directory or array of directories
     *
     * @param string|string[]|null  $directory    Path to directory of models or array of directory paths
     * @throws Exception
     */
    public static function loadModels(string|array|null $directory): array
    {
        $loadedModels = [];

        if ($directory !== null) {
            foreach ((array) $directory as $dir) {
                $dir = rtrim($dir, '/');
                if (!is_dir($dir)) {
                    throw new Exception('You must pass a valid path to a directory containing Doctrine models');
                }

                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($it as $file) {
                    $e = explode('.', $file->getFileName());

                    if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                        $className = $e[0];

                        if (!class_exists($className, false)) {
                            self::loadModel($className, $file->getPathName());
                            $loadedModels[$className] = $className;
                        } elseif (self::isValidModelClass($className)) {
                            $loadedModels[$className] = $className;
                        }
                    }
                }
            }
        }

        asort($loadedModels);

        return $loadedModels;
    }

    /**
     * Get all the loaded models, you can provide an array of classes or it will use get_declared_classes()
     *
     * Will filter through an array of classes and return the Records out of them.
     * If you do not specify $classes it will return all of the currently loaded Records
     *
     * @param  array|null $classes Array of classes to filter through, otherwise uses get_declared_classes()
     * @phpstan-param list<class-string<Record>> $classes
     * @phpstan-return list<class-string<Record>>
     */
    public static function getLoadedModels(?array $classes = null): array
    {
        if ($classes === null) {
            $classes = get_declared_classes();
            $classes = array_merge($classes, array_keys(self::$loadedModelFiles));
        }

        return self::filterInvalidModels($classes);
    }

    /**
     * Initialize all models so everything is present and loaded in to memory
     * This will also inheritently initialize any model behaviors and add
     * them to the $models array
     * @phpstan-param string[] $models
     * @phpstan-return list<class-string<Record>>
     */
    public static function initializeModels(array $models): array
    {
        $models = self::filterInvalidModels($models);
        /** @phpstan-var class-string<Record>[] $models */

        foreach ($models as $model) {
            $declaredBefore = get_declared_classes();
            Core::getTable($model);

            $declaredAfter = get_declared_classes();
            // Using array_slice because array_diff is broken is some PHP versions
            $foundClasses = array_slice($declaredAfter, count($declaredBefore) - 1);
            foreach ($foundClasses as $class) {
                if (self::isValidModelClass($class)) {
                    $models[] = $class;
                }
            }
        }

        return self::filterInvalidModels($models);
    }

    /**
     * Filter through an array of classes and return all the classes that are valid models.
     * This will inflect the class, causing it to be loaded in to memory.
     * @phpstan-param string[] $classes
     * @phpstan-return list<class-string<Record>>
     */
    public static function filterInvalidModels(array $classes): array
    {
        $validModels = [];

        foreach ($classes as $name) {
            if (self::isValidModelClass($name) && !in_array($name, $validModels)) {
                /** @phpstan-var class-string<Record> $name */
                $validModels[] = $name;
            }
        }

        return $validModels;
    }

    /**
     * Checks if what is passed is a valid Record
     * Will load class in to memory in order to inflect it and find out information about the class
     *
     * @param  mixed $class Can be a string named after the class, an instance of the class, or an instance of the class reflected
     */
    public static function isValidModelClass(mixed $class): bool
    {
        if ($class instanceof Record) {
            $class = get_class($class);
        }

        if (is_string($class) && class_exists($class)) {
            $class = new \ReflectionClass($class);
        }

        if ($class instanceof \ReflectionClass) {
            // Skip the following classes
            // - abstract classes
            // - not a subclass of Record
            if (!$class->isAbstract() && $class->isSubclassOf(Record::class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method for importing existing schema to Record classes
     *
     * @param  string $directory   Directory to write your models to
     * @param  array  $connections Array of connection names to generate models for
     * @throws \Throwable
     */
    public static function generateModelsFromDb(string $directory, array $connections = [], array $options = []): array
    {
        return Manager::connection()->import->importSchema($directory, $connections, $options);
    }

    /**
     * Generates models from database to temporary location then uses those models to generate a yaml schema file.
     * This should probably be fixed. We should write something to generate a yaml schema file directly from the database.
     *
     * @param string $yamlPath Path to write oyur yaml schema file to
     * @param array $connections Array of connection names to generate yaml for
     * @param array $options Array of options
     * @throws Exception
     */
    public static function generateYamlFromDb(string $yamlPath, array $connections = [], array $options = []): int|string|null
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tmp_doctrine_models';

        $options['generateBaseClasses'] = isset($options['generateBaseClasses']) ? $options['generateBaseClasses'] : false;
        $result = Core::generateModelsFromDb($directory, $connections, $options);

        if (empty($result) && !is_dir($directory)) {
            throw new Exception('No models generated from your databases');
        }

        $export = new Export\Schema();

        $result = $export->exportSchema($yamlPath, 'yml', $directory, []);

        Lib::removeDirectories($directory);

        return $result;
    }

    /**
     * Generate a yaml schema file from an existing directory of models
     *
     * @param  string $yamlPath  Path to your yaml schema files
     * @param  string $directory Directory to generate your models in
     * @param  array  $options   Array of options to pass to the schema importer
     */
    public static function generateModelsFromYaml(string $yamlPath, string $directory, array $options = []): void
    {
        $import = new Import\Schema();
        $import->setOptions($options);

        $import->importSchema([$yamlPath], 'yml', $directory);
    }

    /**
     * Creates database tables for the models in the specified directory
     *
     * @param  string $directory Directory containing your models
     * @return void
     */
    public static function createTablesFromModels($directory = null)
    {
        Manager::connection()->export->exportSchema($directory);
    }

    /**
     * Creates database tables for the models in the supplied array
     *
     * @param  array $array An array of models to be exported
     * @return void
     */
    public static function createTablesFromArray($array)
    {
        Manager::connection()->export->exportClasses($array);
    }

    /**
     * Generate a array of sql for the passed array of models
     *
     * @param  array $array
     * @return array $sql
     */
    public static function generateSqlFromArray($array)
    {
        return Manager::connection()->export->exportClassesSql($array);
    }

    /**
     * Generate a sql string to create the tables from all loaded models
     * or the models found in the passed directory.
     *
     * @param  string $directory
     * @return string $build  String of sql queries. One query per line
     */
    public static function generateSqlFromModels($directory = null)
    {
        $conn = Manager::connection();
        $sql  = $conn->export->exportSql($directory);

        $build = '';
        foreach ($sql as $query) {
            $build .= $query . $conn->sql_file_delimiter;
        }

        return $build;
    }

    /**
     * Generate yaml schema file for the models in the specified directory
     *
     * @param string $yamlPath  Path to your yaml schema files
     * @param string $directory Directory to generate your models in
     */
    public static function generateYamlFromModels(string $yamlPath, string $directory): int|string|null
    {
        $export = new Export\Schema();

        return $export->exportSchema($yamlPath, 'yml', $directory);
    }

    /**
     * Creates databases for connections
     *
     * @param array $specifiedConnections Array of connections you wish to create the database for
     */
    public static function createDatabases(array $specifiedConnections = []): void
    {
        Manager::getInstance()->createDatabases($specifiedConnections);
    }

    /**
     * Drops databases for connections
     *
     * @param array $specifiedConnections Array of connections you wish to drop the database for
     */
    public static function dropDatabases(array $specifiedConnections = []): void
    {
        Manager::getInstance()->dropDatabases($specifiedConnections);
    }

    /**
     * Load data from a yaml fixtures file.
     * The output of dumpData can be fed to loadData
     *
     * @param string $yamlPath Path to your yaml data fixtures
     * @param bool   $append   Whether or not to append the data
     */
    public static function loadData(string $yamlPath, bool $append = false): void
    {
        $data = new Data();
        $data->importData($yamlPath, 'yml', [], $append);
    }

    /**
     * Get the Table object for the passed model
     *
     * @phpstan-param class-string<Record> $componentName
     */
    public static function getTable(string $componentName): Table
    {
        return Manager::getInstance()->getConnectionForComponent($componentName)->getTable($componentName);
    }

    public static function modelsAutoload(string $className): bool
    {
        if (class_exists($className, false) || interface_exists($className, false)) {
            return false;
        }

        if (!self::$modelsDirectory) {
            $loadedModels = self::$loadedModelFiles;

            if (isset($loadedModels[$className]) && file_exists($loadedModels[$className])) {
                include $loadedModels[$className];

                return true;
            }
        } else {
            $class = self::$modelsDirectory . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

            if (file_exists($class)) {
                include $class;

                return true;
            }
        }

        return false;
    }
}
