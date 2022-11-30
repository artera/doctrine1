<?php

namespace Doctrine1;

/**
 * The base core class of Doctrine
 */
class Core
{
    /**
     * VERSION
     */
    const VERSION = '1.2.4';

    /**
     * ERROR CONSTANTS
     */
    const ERR                     = -1;
    const ERR_SYNTAX              = -2;
    const ERR_CONSTRAINT          = -3;
    const ERR_NOT_FOUND           = -4;
    const ERR_ALREADY_EXISTS      = -5;
    const ERR_UNSUPPORTED         = -6;
    const ERR_MISMATCH            = -7;
    const ERR_INVALID             = -8;
    const ERR_NOT_CAPABLE         = -9;
    const ERR_TRUNCATED           = -10;
    const ERR_INVALID_NUMBER      = -11;
    const ERR_INVALID_DATE        = -12;
    const ERR_DIVZERO             = -13;
    const ERR_NODBSELECTED        = -14;
    const ERR_CANNOT_CREATE       = -15;
    const ERR_CANNOT_DELETE       = -16;
    const ERR_CANNOT_DROP         = -17;
    const ERR_NOSUCHTABLE         = -18;
    const ERR_NOSUCHFIELD         = -19;
    const ERR_NEED_MORE_DATA      = -20;
    const ERR_NOT_LOCKED          = -21;
    const ERR_VALUE_COUNT_ON_ROW  = -22;
    const ERR_INVALID_DSN         = -23;
    const ERR_CONNECT_FAILED      = -24;
    const ERR_NOSUCHDB            = -26;
    const ERR_ACCESS_VIOLATION    = -27;
    const ERR_CANNOT_REPLACE      = -28;
    const ERR_CONSTRAINT_NOT_NULL = -29;
    const ERR_DEADLOCK            = -30;
    const ERR_CANNOT_ALTER        = -31;
    const ERR_MANAGER             = -32;
    const ERR_MANAGER_PARSE       = -33;
    const ERR_LOADMODULE          = -34;
    const ERR_INSUFFICIENT_DATA   = -35;
    const ERR_CLASS_NAME          = -36;

    /**
     * PDO derived constants
     */
    const CASE_LOWER         = 2;
    const CASE_NATURAL       = 0;
    const CASE_UPPER         = 1;
    const CURSOR_FWDONLY     = 0;
    const CURSOR_SCROLL      = 1;
    const ERRMODE_EXCEPTION  = 2;
    const ERRMODE_SILENT     = 0;
    const ERRMODE_WARNING    = 1;
    const FETCH_ASSOC        = 2;
    const FETCH_BOTH         = 4;
    const FETCH_BOUND        = 6;
    const FETCH_CLASS        = 8;
    const FETCH_CLASSTYPE    = 262144;
    const FETCH_COLUMN       = 7;
    const FETCH_FUNC         = 10;
    const FETCH_GROUP        = 65536;
    const FETCH_INTO         = 9;
    const FETCH_LAZY         = 1;
    const FETCH_NAMED        = 11;
    const FETCH_NUM          = 3;
    const FETCH_OBJ          = 5;
    const FETCH_ORI_ABS      = 4;
    const FETCH_ORI_FIRST    = 2;
    const FETCH_ORI_LAST     = 3;
    const FETCH_ORI_NEXT     = 0;
    const FETCH_ORI_PRIOR    = 1;
    const FETCH_ORI_REL      = 5;
    const FETCH_SERIALIZE    = 524288;
    const FETCH_UNIQUE       = 196608;
    const NULL_EMPTY_STRING  = 1;
    const NULL_NATURAL       = 0;
    const NULL_TO_STRING     = null;
    const PARAM_BOOL         = 5;
    const PARAM_INPUT_OUTPUT = -2147483648;
    const PARAM_INT          = 1;
    const PARAM_LOB          = 3;
    const PARAM_NULL         = 0;
    const PARAM_STMT         = 4;
    const PARAM_STR          = 2;

    /**
     * ATTRIBUTE CONSTANTS
     */

    /**
     * PDO derived attributes
     */
    const ATTR_AUTOCOMMIT          = 0;
    const ATTR_PREFETCH            = 1;
    const ATTR_TIMEOUT             = 2;
    const ATTR_ERRMODE             = 3;
    const ATTR_SERVER_VERSION      = 4;
    const ATTR_CLIENT_VERSION      = 5;
    const ATTR_SERVER_INFO         = 6;
    const ATTR_CONNECTION_STATUS   = 7;
    const ATTR_CASE                = 8;
    const ATTR_CURSOR_NAME         = 9;
    const ATTR_CURSOR              = 10;
    const ATTR_PERSISTENT          = 12;
    const ATTR_STATEMENT_CLASS     = 13;
    const ATTR_FETCH_TABLE_NAMES   = 14;
    const ATTR_FETCH_CATALOG_NAMES = 15;
    const ATTR_DRIVER_NAME         = 16;
    const ATTR_STRINGIFY_FETCHES   = 17;
    const ATTR_MAX_COLUMN_LEN      = 18;

    /**
     * Doctrine constants
     */
    const ATTR_LISTENER         = 100;
    const ATTR_QUOTE_IDENTIFIER = 101;
    const ATTR_FIELD_CASE       = 102;
    const ATTR_IDXNAME_FORMAT   = 103;
    const ATTR_SEQNAME_FORMAT   = 104;
    const ATTR_SEQCOL_NAME      = 105;
    const ATTR_CMPNAME_FORMAT   = 118;
    const ATTR_DBNAME_FORMAT    = 117;
    const ATTR_TBLCLASS_FORMAT  = 119;
    const ATTR_TBLNAME_FORMAT   = 120;
    const ATTR_FKNAME_FORMAT    = 171;
    const ATTR_EXPORT           = 140;
    const ATTR_DECIMAL_PLACES   = 141;

    const ATTR_PORTABILITY        = 106;
    const ATTR_VALIDATE           = 107;
    const ATTR_COLL_KEY           = 108;
    const ATTR_QUERY_LIMIT        = 109;
    const ATTR_DEFAULT_TABLE_TYPE = 112;
    const ATTR_DEF_TEXT_LENGTH    = 113;
    const ATTR_DEF_VARCHAR_LENGTH = 114;
    const ATTR_DEF_TABLESPACE     = 115;
    const ATTR_EMULATE_DATABASE   = 116;
    const ATTR_USE_NATIVE_ENUM    = 117;
    const ATTR_DEFAULT_SEQUENCE   = 133;

    const ATTR_FETCHMODE     = 118;
    const ATTR_NAME_PREFIX   = 121;
    const ATTR_CREATE_TABLES = 122;
    const ATTR_COLL_LIMIT    = 123;

    const ATTR_CACHE                      = 150;
    const ATTR_RESULT_CACHE               = 150;
    const ATTR_CACHE_LIFESPAN             = 151;
    const ATTR_RESULT_CACHE_LIFESPAN      = 151;
    const ATTR_LOAD_REFERENCES            = 153;
    const ATTR_RECORD_LISTENER            = 154;
    const ATTR_DEFAULT_PARAM_NAMESPACE    = 156;
    const ATTR_QUERY_CACHE                = 157;
    const ATTR_QUERY_CACHE_LIFESPAN       = 158;
    const ATTR_AUTOLOAD_TABLE_CLASSES     = 160;
    const ATTR_MODEL_LOADING              = 161;
    const ATTR_RECURSIVE_MERGE_FIXTURES   = 162;
    const ATTR_USE_DQL_CALLBACKS          = 164;
    const ATTR_AUTO_ACCESSOR_OVERRIDE     = 165;
    const ATTR_AUTO_FREE_QUERY_OBJECTS    = 166;
    const ATTR_DEFAULT_TABLE_CHARSET      = 167;
    const ATTR_DEFAULT_TABLE_COLLATE      = 168;
    const ATTR_DEFAULT_IDENTIFIER_OPTIONS = 169;
    const ATTR_DEFAULT_COLUMN_OPTIONS     = 170;
    const ATTR_HYDRATE_OVERWRITE          = 172;
    const ATTR_QUERY_CLASS                = 173;
    const ATTR_CASCADE_SAVES              = 174;
    const ATTR_COLLECTION_CLASS           = 175;
    const ATTR_TABLE_CLASS                = 176;
    const ATTR_USE_NATIVE_SET             = 177;
    const ATTR_MODEL_CLASS_PREFIX         = 178;
    const ATTR_TABLE_CLASS_FORMAT         = 179;
    const ATTR_MAX_IDENTIFIER_LENGTH      = 180;

    /**
     * LIMIT CONSTANTS
     */

    /**
     * constant for row limiting
     */
    const LIMIT_ROWS       = 1;
    const QUERY_LIMIT_ROWS = 1;

    /**
     * constant for record limiting
     */
    const LIMIT_RECORDS       = 2;
    const QUERY_LIMIT_RECORDS = 2;

    /**
     * FETCHMODE CONSTANTS
     */


    /**
     * PORTABILITY CONSTANTS
     */

    /**
     * Portability: turn off all portability features.
     *
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_NONE = 0;

    /**
     * Portability: convert names of tables and fields to case defined in the
     * "field_case" option when using the query*(), fetch*() methods.
     *
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_FIX_CASE = 1;

    /**
     * Portability: right trim the data output by query*() and fetch*().
     *
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_RTRIM = 2;

    /**
     * Portability: force reporting the number of rows deleted.
     *
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_DELETE_COUNT = 4;

    /**
     * Portability: convert empty values to null strings in data output by
     * query*() and fetch*().
     *
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_EMPTY_TO_NULL = 8;

    /**
     * Portability: removes database/table qualifiers from associative indexes
     *
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_FIX_ASSOC_FIELD_NAMES = 16;

    /**
     * Portability: makes Expression throw exception for unportable RDBMS expressions
     *
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_EXPR = 32;

    /**
     * Portability: turn on all portability features.
     *
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_ALL = 63;

    /**
     * LOCKMODE CONSTANTS
     */

    /**
     * mode for optimistic locking
     */
    const LOCK_OPTIMISTIC = 0;

    /**
     * mode for pessimistic locking
     */
    const LOCK_PESSIMISTIC = 1;

    /**
     * EXPORT CONSTANTS
     */

    /**
     * EXPORT_NONE
     */
    const EXPORT_NONE = 0;

    /**
     * EXPORT_TABLES
     */
    const EXPORT_TABLES = 1;

    /**
     * EXPORT_CONSTRAINTS
     */
    const EXPORT_CONSTRAINTS = 2;

    /**
     * EXPORT_PLUGINS
     */
    const EXPORT_PLUGINS = 4;

    /**
     * EXPORT_ALL
     */
    const EXPORT_ALL = 7;

    /**
     * HYDRATION CONSTANTS
     */

    /**
     * HYDRATE_RECORD
     */
    const HYDRATE_RECORD = 2;

    /**
     * HYDRATE_ARRAY
     */
    const HYDRATE_ARRAY = 3;

    /**
     * HYDRATE_NONE
     */
    const HYDRATE_NONE = 4;

    /**
     * HYDRATE_SCALAR
     */
    const HYDRATE_SCALAR = 5;

    /**
     * HYDRATE_SINGLE_SCALAR
     */
    const HYDRATE_SINGLE_SCALAR = 6;

    /**
     * HYDRATE_ON_DEMAND
     */
    const HYDRATE_ON_DEMAND = 7;

    /**
     * HYDRATE_ARRAY_SHALLOW
     */
    const HYDRATE_ARRAY_SHALLOW = 10;

    /**
     * VALIDATION CONSTANTS
     */
    const VALIDATE_NONE = 0;

    /**
     * VALIDATE_LENGTHS
     */
    const VALIDATE_LENGTHS = 1;

    /**
     * VALIDATE_TYPES
     */
    const VALIDATE_TYPES = 2;

    /**
     * VALIDATE_CONSTRAINTS
     */
    const VALIDATE_CONSTRAINTS = 4;

    /**
     * VALIDATE_ALL
     */
    const VALIDATE_ALL = 7;

    /**
     * VALIDATE_USER
     */
    const VALIDATE_USER = 8;

    /**
     * IDENTIFIER_AUTOINC
     *
     * constant for auto_increment identifier
     */
    const IDENTIFIER_AUTOINC = 1;

    /**
     * IDENTIFIER_SEQUENCE
     *
     * constant for sequence identifier
     */
    const IDENTIFIER_SEQUENCE = 2;

    /**
     * IDENTIFIER_NATURAL
     *
     * constant for normal identifier
     */
    const IDENTIFIER_NATURAL = 3;

    /**
     * IDENTIFIER_COMPOSITE
     *
     * constant for composite identifier
     */
    const IDENTIFIER_COMPOSITE = 4;

    /**
     * MODEL_LOADING_AGGRESSIVE
     *
     * Constant for agressive model loading
     * Will require_once() all found model files
     */
    const MODEL_LOADING_AGGRESSIVE = 1;

    /**
     * MODEL_LOADING_CONSERVATIVE
     *
     * Constant for conservative model loading
     * Will not require_once() found model files inititally instead it will build an array
     * and reference it in autoload() when a class is needed it will require_once() it
     */
    const MODEL_LOADING_CONSERVATIVE = 2;

    /**
     * MODEL_LOADING_PEAR
     *
     * Constant for pear model loading
     * Will simply store the path passed to Core::loadModels()
     * and Core::autoload() will check there
     */
    const MODEL_LOADING_PEAR = 3;

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
     * @param integer|null $modelLoading Pass value of Core::ATTR_MODEL_LOADING to force a certain style of model loading
     *                              Allowed Core::MODEL_LOADING_AGGRESSIVE(default) or
     *                              Core::MODEL_LOADING_CONSERVATIVE
     * @param string|null  $classPrefix  The class prefix of the models to load. This is useful if the class name and file name are not the same
     * @throws Exception
     */
    public static function loadModels(string|array|null $directory, ?int $modelLoading = null, ?string $classPrefix = null): array
    {
        $manager = Manager::getInstance();

        $modelLoading = $modelLoading === null ? $manager->getAttribute(Core::ATTR_MODEL_LOADING) : $modelLoading;
        $classPrefix  = $classPrefix  === null ? $manager->getAttribute(Core::ATTR_MODEL_CLASS_PREFIX) : $classPrefix;

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
                        if ($modelLoading == Core::MODEL_LOADING_PEAR) {
                            $className = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathName());
                            $className = str_replace(DIRECTORY_SEPARATOR, '_', $className);
                            assert(is_string($className));
                            $className = substr($className, 0, strpos($className, '.') ?: strlen($className));
                        } else {
                            $className = $e[0];
                        }

                        if ($classPrefix && $classPrefix != substr($className, 0, strlen($classPrefix))) {
                            $className = $classPrefix . $className;
                        }

                        if (!class_exists($className, false)) {
                            if ($modelLoading == Core::MODEL_LOADING_CONSERVATIVE || $modelLoading == Core::MODEL_LOADING_PEAR) {
                                self::loadModel($className, $file->getPathName());

                                $loadedModels[$className] = $className;
                            } else {
                                $declaredBefore = get_declared_classes();
                                include_once $file->getPathName();
                                $declaredAfter = get_declared_classes();

                                // Using array_slice because array_diff is broken is some PHP versions
                                $foundClasses = array_slice($declaredAfter, count($declaredBefore));

                                if ($foundClasses) {
                                    foreach ($foundClasses as $className) {
                                        if (self::isValidModelClass($className)) {
                                            $loadedModels[$className] = $className;

                                            self::loadModel($className, $file->getPathName());
                                        }
                                    }
                                }

                                $previouslyLoaded = array_keys(self::$loadedModelFiles, $file->getPathName());

                                if (!empty($previouslyLoaded)) {
                                    $previouslyLoaded = array_combine(array_values($previouslyLoaded), array_values($previouslyLoaded));
                                    $loadedModels     = array_merge($loadedModels, $previouslyLoaded);
                                }
                            }
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

        $models = self::filterInvalidModels($models);

        return $models;
    }

    /**
     * Filter through an array of classes and return all the classes that are valid models.
     * This will inflect the class, causing it to be loaded in to memory.
     * @phpstan-param string[] $classes
     * @phpstan-return class-string<Record>[]
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

        $options['generateBaseClasses'] = isset($options['generateBaseClasses']) ? $options['generateBaseClasses']:false;
        $result = Core::generateModelsFromDb($directory, $connections, $options);

        if (empty($result) && !is_dir($directory)) {
            throw new Exception('No models generated from your databases');
        }

        $export = new Export\Schema();

        $result = $export->exportSchema($yamlPath, 'yml', $directory, [], Core::MODEL_LOADING_AGGRESSIVE);

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
     * Migrate database to specified $to version. Migrates from current to latest if you do not specify.
     *
     * @param string $migrationsPath Path to migrations directory which contains your migration classes
     * @param int|null    $to             Version you wish to migrate to.
     *
     * @throws Migration\Exception
     */
    public static function migrate(string $migrationsPath, ?int $to = null): int|bool|null
    {
        $migration = new Migration($migrationsPath);
        return $migration->migrate($to);
    }

    /**
     * Generate new migration class skeleton
     *
     * @param  string $className      Name of the Migration class to generate
     * @param  string $migrationsPath Path to directory which contains your migration classes
     * @return mixed
     */
    public static function generateMigrationClass(string $className, string $migrationsPath): mixed
    {
        $builder = new Migration\Builder($migrationsPath);

        return $builder->generateMigrationClass($className);
    }

    /**
     * Generate a set of migration classes from an existing database
     *
     * @param  string $migrationsPath
     * @return bool
     * @throws Migration\Exception
     */
    public static function generateMigrationsFromDb(string $migrationsPath): bool
    {
        $builder = new Migration\Builder($migrationsPath);

        return $builder->generateMigrationsFromDb();
    }

    /**
     * Generate a set of migration classes from an existing set of models
     *
     * @param  string  $migrationsPath Path to your Doctrine migration classes
     * @param  string|null  $modelsPath     Path to your Doctrine model classes
     * @param  integer|null $modelLoading   Style of model loading to use for loading the models in order to generate migrations
     */
    public static function generateMigrationsFromModels(string $migrationsPath, ?string $modelsPath = null, ?int $modelLoading = null): bool
    {
        $builder = new Migration\Builder($migrationsPath);

        return $builder->generateMigrationsFromModels($modelsPath, $modelLoading);
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
