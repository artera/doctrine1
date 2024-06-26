<?php

namespace Doctrine1\Hydrator;

use Doctrine1\Column\Type;
use Doctrine1\HydrationMode;

/**
 * @phpstan-template Collection of \Traversable|array
 * @phpstan-template Item
 */
abstract class Graph extends \Doctrine1\Hydrator\AbstractHydrator
{
    /**
     * This was previously undefined, setting to protected to match \Doctrine1\Hydrator
     */
    protected ?string $rootAlias = null;

    /** @phpstan-var \Doctrine1\Table[] */
    protected array $tables = [];

    /**
     * Gets the custom field used for indexing for the specified component alias.
     *
     * @param  string $alias
     * @return string|null The field name of the field used for indexing or NULL
     *                 if the component does not use any custom field indices.
     */
    protected function getCustomIndexField(string $alias): ?string
    {
        return isset($this->queryComponents[$alias]['map']) ? $this->queryComponents[$alias]['map'] : null;
    }

    /** @return Collection */
    public function hydrateResultSet(\Doctrine1\Connection\Statement $stmt): iterable
    {
        // Used variables during hydration
        reset($this->queryComponents);

        /** @var string */
        $rootAlias         = key($this->queryComponents);
        $this->rootAlias  = $rootAlias;

        $rootComponentName = $this->queryComponents[$rootAlias]['table']->getComponentName();
        // if only one component is involved we can make our lives easier
        $isSimpleQuery = count($this->queryComponents) <= 1;
        // Holds array of record instances so we can call hooks on it
        $instances = [];
        // Holds hydration listeners that get called during hydration
        $listeners = [];
        // Lookup map to quickly discover/lookup existing records in the result
        $identifierMap = [];
        // Holds for each component the last previously seen element in the result set
        $prev = [];
        // holds the values of the identifier/primary key fields of components,
        // separated by a pipe '|' and grouped by component alias (r, u, i, ... whatever)
        // the $idTemplate is a prepared template. $id is set to a fresh template when
        // starting to process a row.
        $id = [];
        $idTemplate = [];

        // Initialize
        foreach ($this->queryComponents as $dqlAlias => $data) {
            $componentName = $data['table']->getComponentName();
            $instances[$componentName] = $data['table']->getRecordInstance();
            $listeners[$componentName] = $data['table']->getRecordListener();
            $identifierMap[$dqlAlias] = [];
            $prev[$dqlAlias] = null;
            $idTemplate[$dqlAlias] = '';
        }
        $cache = [];

        $result = $this->getElementCollection($rootComponentName);
        if ($rootAlias && $result instanceof \Doctrine1\Collection && $indexField = $this->getCustomIndexField($rootAlias)) {
            $result->setKeyColumn($indexField);
        }

        // Process result set
        $cache = [];

        $event = new \Doctrine1\Event(null, \Doctrine1\Event::HYDRATE, null);

        if ($this->hydrationMode == HydrationMode::OnDemand) {
            if ($this->priorRow !== null) {
                $data            = $this->priorRow;
                $this->priorRow = null;
            } else {
                $data = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$data) {
                    return $result;
                }
            }
            $activeRootIdentifier = null;
        } else {
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$data) {
                return $result;
            }
        }

        do {
            $table = $this->queryComponents[$rootAlias]['table'];

            if ($table->getConnection()->getPortability() & \Doctrine1\Core::PORTABILITY_RTRIM) {
                array_map('rtrim', $data);
            }

            $id = $idTemplate; // initialize the id-memory
            $nonemptyComponents = [];
            $rowData = $this->gatherRowData($data, $cache, $id, $nonemptyComponents);

            if ($this->hydrationMode == HydrationMode::OnDemand) {
                if (!isset($activeRootIdentifier)) {
                    // first row for this record
                    $activeRootIdentifier = $id[$rootAlias];
                } elseif ($activeRootIdentifier !== $id[$rootAlias]) {
                    // first row for the next record
                    $this->priorRow = $data;
                    return $result;
                }
            }

            //
            // hydrate the data of the root component from the current row
            //
            $componentName = $table->getComponentName();
            // Ticket #1115 (getInvoker() should return the component that has addEventListener)
            $event->setInvoker($table);
            $event->set('data', $rowData[$rootAlias]);
            $listeners[$componentName]->preHydrate($event);
            $instances[$componentName]->preHydrate($event);

            $index = false;

            // Check for an existing element
            if ($isSimpleQuery || !isset($identifierMap[$rootAlias][$id[$rootAlias]])) {
                $element = $this->getElement($rowData[$rootAlias], $componentName);
                $event->set('data', $element);
                $listeners[$componentName]->postHydrate($event);
                $instances[$componentName]->postHydrate($event);

                // do we need to index by a custom field?
                if ($field = $this->getCustomIndexField($rootAlias)) {
                    if (!isset($element[$field])) {
                        throw new \Doctrine1\Hydrator\Exception("Couldn't hydrate. Found a non-existent key named '$field'.");
                    } elseif (isset($result[$element[$field]])) {
                        throw new \Doctrine1\Hydrator\Exception("Couldn't hydrate. Found non-unique key mapping named '{$element[$field]}' for the field named '$field'.");
                    }
                    $result[$element[$field]] = $element;
                } else {
                    $result[] = $element;
                }

                $identifierMap[$rootAlias][$id[$rootAlias]] = $this->getLastKey($result);
            } else {
                $index = $identifierMap[$rootAlias][$id[$rootAlias]];
            }

            $this->setLastElement($prev, $result, $index, $rootAlias, false);
            unset($rowData[$rootAlias]);

            // end hydrate data of the root component for the current row

            // $prev[$rootAlias] now points to the last element in $result.
            // now hydrate the rest of the data found in the current row, that belongs to other
            // (related) components.
            foreach ($rowData as $dqlAlias => $data) {
                $index         = false;
                $map           = $this->queryComponents[$dqlAlias];
                $table         = $map['table'];
                $componentName = $table->getComponentName();
                $event->set('data', $data);
                $event->setInvoker($table);
                $listeners[$componentName]->preHydrate($event);
                $instances[$componentName]->preHydrate($event);

                // It would be nice if this could be moved to the query parser but I could not find a good place to implement it
                if (!isset($map['parent']) || !isset($map['relation'])) {
                    throw new \Doctrine1\Hydrator\Exception(
                        '"' . $componentName . '" with an alias of "' . $dqlAlias . '"' .
                        ' in your query does not reference the parent component it is related to.'
                    );
                }

                $parent        = $map['parent'];
                $relation      = $map['relation'];
                $relationAlias = $map['relation']->getAlias();

                $path = $parent . '.' . $dqlAlias;

                if (!isset($prev[$parent])) {
                    unset($prev[$dqlAlias]); // Ticket #1228
                    continue;
                }

                $indexField = $this->getCustomIndexField($dqlAlias);

                // check the type of the relation
                if (!$relation->isOneToOne() && $this->initRelated($prev[$parent], $relationAlias, $indexField)) {
                    $oneToOne = false;
                    // append element
                    if (isset($nonemptyComponents[$dqlAlias])) {
                        $indexExists  = isset($identifierMap[$path][$id[$parent]][$id[$dqlAlias]]);
                        $index        = $indexExists ? $identifierMap[$path][$id[$parent]][$id[$dqlAlias]] : false;
                        $indexIsValid = $index !== false ? isset($prev[$parent][$relationAlias][$index]) : false;
                        if (!$indexExists || !$indexIsValid) {
                            $element = $this->getElement($data, $componentName);
                            $event->set('data', $element);
                            $listeners[$componentName]->postHydrate($event);
                            $instances[$componentName]->postHydrate($event);

                            if ($field = $this->getCustomIndexField($dqlAlias)) {
                                if (!isset($element[$field])) {
                                    throw new \Doctrine1\Hydrator\Exception("Couldn't hydrate. Found a non-existent key named '$field'.");
                                } elseif (isset($prev[$parent][$relationAlias][$element[$field]])) {
                                    throw new \Doctrine1\Hydrator\Exception("Couldn't hydrate. Found non-unique key mapping named '$field'.");
                                }
                                $prev[$parent][$relationAlias][$element[$field]] = $element;
                            } else {
                                $prev[$parent][$relationAlias][] = $element;
                            }
                            $identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $this->getLastKey($prev[$parent][$relationAlias]);
                        }
                        $collection = $prev[$parent][$relationAlias];
                        if ($collection instanceof \Doctrine1\Collection) {
                            if ($indexField) {
                                $collection->setKeyColumn($indexField);
                            }
                            // register collection for later snapshots
                            $this->registerCollection($collection);
                        }
                    }
                } else {
                    // 1-1 relation
                    $oneToOne = true;
                    if (!($prev[$parent] instanceof \Doctrine1\Record ? $prev[$parent]->contains($relationAlias, load: false) : isset($prev[$parent][$relationAlias]))) {
                        if (!isset($nonemptyComponents[$dqlAlias])) {
                            $prev[$parent][$relationAlias] = $this->getNullPointer();
                        } else {
                            $element = $this->getElement($data, $componentName);

                            // [FIX] Tickets #1205 and #1237
                            $event->set('data', $element);
                            $listeners[$componentName]->postHydrate($event);
                            $instances[$componentName]->postHydrate($event);

                            $prev[$parent][$relationAlias] = $element;
                        }
                    }
                }
                if ($prev[$parent][$relationAlias] !== null) {
                    $coll = & $prev[$parent][$relationAlias];
                    $this->setLastElement($prev, $coll, $index, $dqlAlias, $oneToOne);
                }
            }
        } while ($data = $stmt->fetch(\PDO::FETCH_ASSOC));

        $stmt->closeCursor();
        $this->flush();

        return $result;
    }

    /**
     * Puts the fields of a data row into a new array, grouped by the component
     * they belong to. The column names in the result set are mapped to their
     * field names during this procedure.
     *
     * @param  array $data
     * @param  array $cache
     * @param  array $id
     * @param  array $nonemptyComponents
     * @return array  An array with all the fields (name => value) of the data row,
     *                grouped by their component (alias).
     */
    protected function gatherRowData(&$data, &$cache, &$id, &$nonemptyComponents)
    {
        $rowData = [];

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if (!isset($cache[$key])) {
                // check ignored names. fastest solution for now. if we get more we'll start
                // to introduce a list.
                if ($this->isIgnoredName($key)) {
                    continue;
                }

                $e                        = explode('__', $key);
                $last                     = strtolower(array_pop($e));
                $cache[$key]['dqlAlias']  = $this->tableAliases[strtolower(implode('__', $e))];
                $table                    = $this->queryComponents[$cache[$key]['dqlAlias']]['table'];
                $fieldName                = $table->getFieldName($last);
                $cache[$key]['fieldName'] = $fieldName;
                if ($table->isIdentifier($fieldName)) {
                    $cache[$key]['isIdentifier'] = true;
                } else {
                    $cache[$key]['isIdentifier'] = false;
                }
                $type = $table->getTypeOfColumn($last);
                if ($type === Type::Integer || $type === Type::String) {
                    $cache[$key]['isSimpleType'] = true;
                } else {
                    $cache[$key]['type']         = $type;
                    $cache[$key]['isSimpleType'] = false;
                }
            }

            $map       = $this->queryComponents[$cache[$key]['dqlAlias']];
            $table     = $map['table'];
            $dqlAlias  = $cache[$key]['dqlAlias'];
            $fieldName = $cache[$key]['fieldName'];
            $agg       = false;
            if (isset($this->queryComponents[$dqlAlias]['agg']) && isset($this->queryComponents[$dqlAlias]['agg'][$fieldName])) {
                $fieldName = $this->queryComponents[$dqlAlias]['agg'][$fieldName];
                $agg       = true;
            }

            if ($cache[$key]['isIdentifier']) {
                $id[$dqlAlias] .= '|' . $value;
            }

            if ($cache[$key]['isSimpleType']) {
                $preparedValue = $value;
            } else {
                $preparedValue = $table->prepareValue($fieldName, $value, $cache[$key]['type']);
            }

            // Ticket #1380
            // Hydrate aggregates in to the root component as well.
            // So we know that all aggregate values will always be available in the root component
            if ($agg) {
                $rowData[$this->rootAlias][$fieldName] = $preparedValue;
                if (isset($rowData[$dqlAlias])) {
                    $rowData[$dqlAlias][$fieldName] = $preparedValue;
                }
            } else {
                $rowData[$dqlAlias][$fieldName] = $preparedValue;
            }

            if (!isset($nonemptyComponents[$dqlAlias]) && $value !== null) {
                $nonemptyComponents[$dqlAlias] = true;
            }
        }

        return $rowData;
    }

    /** @return Collection */
    abstract public function getElementCollection(string $component);

    abstract public function registerCollection(\Doctrine1\Collection $coll): void;

    /** @param Item $record */
    abstract public function initRelated(&$record, string $name, ?string $keyColumn = null): bool;

    abstract public function getNullPointer(): ?\Doctrine1\None;

    /**
     * @phpstan-param class-string<\Doctrine1\Record> $component
     * @return Item
     */
    abstract public function getElement(array $data, string $component);

    /** @param Collection $coll */
    abstract public function getLastKey(&$coll): mixed;

    /** @param null|\Doctrine1\None|Collection $coll */
    abstract public function setLastElement(array &$prev, &$coll, int|bool $index, string $dqlAlias, bool $oneToOne): void;

    public function flush(): void
    {
    }

    /**
     * Get the classname to return. Most often this is just the options['name']
     *
     * Check the subclasses option and the inheritanceMap for each subclass to see
     * if all the maps in a subclass is met. If this is the case return that
     * subclass name. If no subclasses match or if there are no subclasses defined
     * return the name of the class for this tables record.
     *
     * @todo this function could use reflection to check the first time it runs
     * if the subclassing option is not set.
     *
     * @phpstan-param class-string<\Doctrine1\Record> $component
     * @return string The name of the class to create
     */
    protected function getClassnameToReturn(array &$data, $component): string
    {
        if (!isset($this->tables[$component])) {
            $this->tables[$component] = \Doctrine1\Core::getTable($component);
        }

        /** @phpstan-var class-string<\Doctrine1\Record>[] */
        $subclasses = $this->tables[$component]->subclasses;
        if (!$subclasses) {
            return $component;
        }

        $matchedComponents = [$component];
        foreach ($subclasses as $subclass) {
            $table          = \Doctrine1\Core::getTable($subclass);
            $inheritanceMap = $table->inheritanceMap;
            $needMatches    = count($inheritanceMap);
            foreach ($inheritanceMap as $key => $value) {
                $key = $this->tables[$component]->getFieldName($key);
                if (isset($data[$key]) && $data[$key] == $value) {
                    --$needMatches;
                }
            }
            if ($needMatches == 0) {
                $matchedComponents[] = $table->getComponentName();
            }
        }

        $matchedComponent = $matchedComponents[count($matchedComponents) - 1];

        if (!isset($this->tables[$matchedComponent])) {
            $this->tables[$matchedComponent] = \Doctrine1\Core::getTable($matchedComponent);
        }

        return $matchedComponent;
    }
}
