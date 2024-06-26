<?php

namespace Doctrine1\Data;

class Import extends \Doctrine1\Data
{
    /**
     * Array of imported objects for processing and saving
     *
     * @var \Doctrine1\Record[]
     */
    protected $importedObjects = [];

    /**
     * Array of the raw data parsed from yaml
     * @phpstan-var array<class-string<\Doctrine1\Record>, array<string, mixed>>
     */
    protected array $rows = [];

    /**
     * Optionally pass the directory/path to the yaml for importing
     */
    public function __construct(?string $directory = null)
    {
        if ($directory !== null) {
            $this->setDirectory($directory);
        }
    }

    /**
     * Do the parsing of the yaml files and return the final parsed array
     * @phpstan-return array<class-string<\Doctrine1\Record>, mixed>
     */
    public function doParsing(): array
    {
        $directory      = $this->getDirectory();

        $array = [];

        if ($directory !== null) {
            foreach ((array) $directory as $dir) {
                $e = explode('.', $dir);

                // If they specified a specific yml file
                if (end($e) == 'yml') {
                    /** @var array<class-string<\Doctrine1\Record>, mixed> */
                    $array = array_merge_recursive($array, \Doctrine1\Parser::load($dir, $this->getFormat()));
                // If they specified a directory
                } elseif (is_dir($dir)) {
                    $it = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($dir),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );
                    $filesOrdered = [];
                    foreach ($it as $file) {
                        $filesOrdered[] = $file;
                    }
                    // force correct order
                    natcasesort($filesOrdered);
                    foreach ($filesOrdered as $file) {
                        $e = explode('.', $file->getFileName());
                        if (in_array(end($e), $this->getFormats())) {
                            /** @var array<class-string<\Doctrine1\Record>, mixed> */
                            $array = array_merge_recursive($array, \Doctrine1\Parser::load($file->getPathName(), $this->getFormat()));
                        }
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Do the importing of the data parsed from the fixtures
     */
    public function doImport(bool $append = false): void
    {
        $array = $this->doParsing();

        if (!$append) {
            $this->purge(array_reverse(array_keys($array)));
        }

        $this->loadData($array);
    }

    /**
     * Recursively loop over all data fixtures and build the array of className rows
     * @phpstan-param class-string<\Doctrine1\Record> $className
     */
    protected function buildRows(string $className, array $data): void
    {
        $table = \Doctrine1\Core::getTable($className);

        foreach ($data as $rowKey => $row) {
            // do the same for the row information
            $this->rows[$className][$rowKey] = $row;

            foreach ((array) $row as $key => $value) {
                if ($table->hasRelation($key) && is_array($value)) {
                    // Skip associative arrays defining keys to relationships
                    if (!isset($value[0]) || is_array($value[0])) {
                        $rel          = $table->getRelation($key);
                        $relClassName = $rel->getTable()->name;
                        $relRowKey    = $rowKey . '_' . $relClassName;

                        if ($rel->getType() == \Doctrine1\Relation::ONE) {
                            $val                                    = [$relRowKey => $value];
                            $this->rows[$className][$rowKey][$key] = $relRowKey;
                        } else {
                            $val                                    = $value;
                            $this->rows[$className][$rowKey][$key] = array_keys($val);
                        }

                        $this->buildRows($relClassName, $val);
                    }
                }
            }
        }
    }

    /**
     * Get the unsaved object for a specified row key and validate that it is the valid object class
     * for the passed record and relation name
     * @throws \Doctrine1\Data\Exception
     */
    protected function getImportedObject(string $rowKey, \Doctrine1\Record $record, string $relationName, string $referringRowKey): \Doctrine1\Record
    {
        $relation = $record->getTable()->getRelation($relationName);
        $rowKey   = $this->getRowKeyPrefix($relation->getTable()) . $rowKey;

        if (!isset($this->importedObjects[$rowKey])) {
            throw new \Doctrine1\Data\Exception(
                sprintf('Invalid row key specified: %s, referred to in %s', $rowKey, $referringRowKey)
            );
        }

        $relatedRowKeyObject = $this->importedObjects[$rowKey];

        $relationClass = $relation->getClass();
        if (!$relatedRowKeyObject instanceof $relationClass) {
            throw new \Doctrine1\Data\Exception(
                sprintf(
                    'Class referred to in "%s" is expected to be "%s" and "%s" was given',
                    $referringRowKey,
                    $relation->getClass(),
                    get_class($relatedRowKeyObject)
                )
            );
        }

        return $relatedRowKeyObject;
    }

    /**
     * Process a row and make all the appropriate relations between the imported data
     */
    protected function processRow(string $rowKey, string|array $row): void
    {
        $obj = $this->importedObjects[$rowKey];

        foreach ((array) $row as $key => $value) {
            if (method_exists($obj, 'set' . \Doctrine1\Inflector::classify($key))) {
                $func = 'set' . \Doctrine1\Inflector::classify($key);
                $obj->$func($value);
            } elseif ($obj->getTable()->hasField($key)) {
                if ($obj->getTable()->getTypeOf($key) == 'object') {
                    $value = unserialize($value);
                }
                $obj->set($key, $value);
            } elseif ($obj->getTable()->hasRelation($key)) {
                if (is_array($value)) {
                    if (isset($value[0]) && !is_array($value[0])) {
                        foreach ($value as $link) {
                            if ($obj->getTable()->getRelation($key)->getType() === \Doctrine1\Relation::ONE) {
                                $obj->set($key, $this->getImportedObject($link, $obj, $key, $rowKey));
                            } elseif ($obj->getTable()->getRelation($key)->getType() === \Doctrine1\Relation::MANY) {
                                $relation = $obj->$key;

                                $relation[] = $this->getImportedObject($link, $obj, $key, $rowKey);
                            }
                        }
                    } else {
                        $obj->$key->fromArray($value);
                    }
                } else {
                    $obj->set($key, $this->getImportedObject($value, $obj, $key, $rowKey));
                }
            } else {
                try {
                    $obj->$key = $value;
                } catch (\Throwable $e) {
                    // used for Doctrine plugin methods
                    if (is_callable([$obj, 'set' . \Doctrine1\Inflector::classify($key)])) {
                        $func = 'set' . \Doctrine1\Inflector::classify($key);
                        $obj->$func($value);
                    } else {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Perform the loading of the data from the passed array
     *
     * @phpstan-param array<class-string<\Doctrine1\Record>, mixed> $array
     */
    protected function loadData(array $array): void
    {
        $specifiedModels = $this->getModels();

        foreach ($array as $className => $data) {
            if (!empty($specifiedModels) && !in_array($className, $specifiedModels)) {
                continue;
            }

            $this->buildRows($className, $data);
        }

        $buildRows = [];
        foreach ($this->rows as $className => $classRows) {
            $rowKeyPrefix = $this->getRowKeyPrefix(\Doctrine1\Core::getTable($className));
            foreach ($classRows as $rowKey => $row) {
                $rowKey                          = $rowKeyPrefix . $rowKey;
                $buildRows[$rowKey]              = $row;
                $this->importedObjects[$rowKey] = new $className();
                $this->importedObjects[$rowKey]->state(\Doctrine1\Record\State::TDIRTY);
            }
        }

        foreach ($buildRows as $rowKey => $row) {
            $this->processRow($rowKey, $row);
        }

        $manager = \Doctrine1\Manager::getInstance();
        foreach ($manager as $connection) {
            $tree = $connection->unitOfWork->buildFlushTree(array_keys($array));

            foreach ($tree as $model) {
                foreach ($this->importedObjects as $obj) {
                    if ($obj instanceof $model) {
                        $obj->save();
                    }
                }
            }
        }
    }

    /**
     * Returns the prefix to use when indexing an object from the supplied table.
     */
    protected function getRowKeyPrefix(\Doctrine1\Table $table): string
    {
        return sprintf('(%s) ', $table->getTableName());
    }
}
