<?php

/**
 * @phpstan-template Collection
 * @phpstan-template Item
 */
abstract class Doctrine_Hydrator_Graph extends Doctrine_Hydrator_Abstract
{
    /**
     * This was previously undefined, setting to protected to match Doctrine_Hydrator
     */
    protected ?string $_rootAlias = null;

    /** @phpstan-var Doctrine_Table[] */
    protected array $_tables = [];

    /**
     * Gets the custom field used for indexing for the specified component alias.
     *
     * @param  string $alias
     * @return string|null The field name of the field used for indexing or NULL
     *                 if the component does not use any custom field indices.
     */
    protected function _getCustomIndexField(string $alias): ?string
    {
        return isset($this->_queryComponents[$alias]['map']) ? $this->_queryComponents[$alias]['map'] : null;
    }

    /** @return Collection */
    public function hydrateResultSet(Doctrine_Connection_Statement $stmt): iterable
    {
        // Used variables during hydration
        reset($this->_queryComponents);

        /** @var string */
        $rootAlias         = key($this->_queryComponents);
        $this->_rootAlias  = $rootAlias;

        $rootComponentName = $this->_queryComponents[$rootAlias]['table']->getComponentName();
        // if only one component is involved we can make our lives easier
        $isSimpleQuery = count($this->_queryComponents) <= 1;
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
        foreach ($this->_queryComponents as $dqlAlias => $data) {
            $componentName = $data['table']->getComponentName();
            $instances[$componentName] = $data['table']->getRecordInstance();
            $listeners[$componentName] = $data['table']->getRecordListener();
            $identifierMap[$dqlAlias] = [];
            $prev[$dqlAlias] = null;
            $idTemplate[$dqlAlias] = '';
        }
        $cache = [];

        $result = $this->getElementCollection($rootComponentName);
        if ($rootAlias && $result instanceof Doctrine_Collection && $indexField = $this->_getCustomIndexField($rootAlias)) {
            $result->setKeyColumn($indexField);
        }

        // Process result set
        $cache = [];

        $event = new Doctrine_Event(null, Doctrine_Event::HYDRATE, null);

        if ($this->_hydrationMode == Doctrine_Core::HYDRATE_ON_DEMAND) {
            if ($this->_priorRow !== null) {
                $data            = $this->_priorRow;
                $this->_priorRow = null;
            } else {
                $data = $stmt->fetch(Doctrine_Core::FETCH_ASSOC);
                if (!$data) {
                    return $result;
                }
            }
            $activeRootIdentifier = null;
        } else {
            $data = $stmt->fetch(Doctrine_Core::FETCH_ASSOC);
            if (!$data) {
                return $result;
            }
        }

        do {
            $table = $this->_queryComponents[$rootAlias]['table'];

            if ($table->getConnection()->getAttribute(Doctrine_Core::ATTR_PORTABILITY) & Doctrine_Core::PORTABILITY_RTRIM) {
                array_map('rtrim', $data);
            }

            $id = $idTemplate; // initialize the id-memory
            $nonemptyComponents = [];
            $rowData = $this->_gatherRowData($data, $cache, $id, $nonemptyComponents);

            if ($this->_hydrationMode == Doctrine_Core::HYDRATE_ON_DEMAND) {
                if (!isset($activeRootIdentifier)) {
                    // first row for this record
                    $activeRootIdentifier = $id[$rootAlias];
                } elseif ($activeRootIdentifier !== $id[$rootAlias]) {
                    // first row for the next record
                    $this->_priorRow = $data;
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
                if ($field = $this->_getCustomIndexField($rootAlias)) {
                    if (!isset($element[$field])) {
                        throw new Doctrine_Hydrator_Exception("Couldn't hydrate. Found a non-existent key named '$field'.");
                    } elseif (isset($result[$element[$field]])) {
                        throw new Doctrine_Hydrator_Exception("Couldn't hydrate. Found non-unique key mapping named '{$element[$field]}' for the field named '$field'.");
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
                $map           = $this->_queryComponents[$dqlAlias];
                $table         = $map['table'];
                $componentName = $table->getComponentName();
                $event->set('data', $data);
                $event->setInvoker($table);
                $listeners[$componentName]->preHydrate($event);
                $instances[$componentName]->preHydrate($event);

                // It would be nice if this could be moved to the query parser but I could not find a good place to implement it
                if (!isset($map['parent']) || !isset($map['relation'])) {
                    throw new Doctrine_Hydrator_Exception(
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

                $indexField = $this->_getCustomIndexField($dqlAlias);

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

                            if ($field = $this->_getCustomIndexField($dqlAlias)) {
                                if (!isset($element[$field])) {
                                    throw new Doctrine_Hydrator_Exception("Couldn't hydrate. Found a non-existent key named '$field'.");
                                } elseif (isset($prev[$parent][$relationAlias][$element[$field]])) {
                                    throw new Doctrine_Hydrator_Exception("Couldn't hydrate. Found non-unique key mapping named '$field'.");
                                }
                                $prev[$parent][$relationAlias][$element[$field]] = $element;
                            } else {
                                $prev[$parent][$relationAlias][] = $element;
                            }
                            $identifierMap[$path][$id[$parent]][$id[$dqlAlias]] = $this->getLastKey($prev[$parent][$relationAlias]);
                        }
                        $collection = $prev[$parent][$relationAlias];
                        if ($collection instanceof Doctrine_Collection) {
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
                    if (!isset($nonemptyComponents[$dqlAlias]) && !isset($prev[$parent][$relationAlias])) {
                        $prev[$parent][$relationAlias] = $this->getNullPointer();
                    } elseif (!isset($prev[$parent][$relationAlias])) {
                        $element = $this->getElement($data, $componentName);

                        // [FIX] Tickets #1205 and #1237
                        $event->set('data', $element);
                        $listeners[$componentName]->postHydrate($event);
                        $instances[$componentName]->postHydrate($event);

                        $prev[$parent][$relationAlias] = $element;
                    }
                }
                if ($prev[$parent][$relationAlias] !== null) {
                    $coll = & $prev[$parent][$relationAlias];
                    $this->setLastElement($prev, $coll, $index, $dqlAlias, $oneToOne);
                }
            }
        } while ($data = $stmt->fetch(Doctrine_Core::FETCH_ASSOC));

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
    protected function _gatherRowData(&$data, &$cache, &$id, &$nonemptyComponents)
    {
        $rowData = [];

        foreach ($data as $key => $value) {
            // Parse each column name only once. Cache the results.
            if (!isset($cache[$key])) {
                // check ignored names. fastest solution for now. if we get more we'll start
                // to introduce a list.
                if ($this->_isIgnoredName($key)) {
                    continue;
                }

                $e                        = explode('__', $key);
                $last                     = strtolower(array_pop($e));
                $cache[$key]['dqlAlias']  = $this->_tableAliases[strtolower(implode('__', $e))];
                $table                    = $this->_queryComponents[$cache[$key]['dqlAlias']]['table'];
                $fieldName                = $table->getFieldName($last);
                $cache[$key]['fieldName'] = $fieldName;
                if ($table->isIdentifier($fieldName)) {
                    $cache[$key]['isIdentifier'] = true;
                } else {
                    $cache[$key]['isIdentifier'] = false;
                }
                $type = $table->getTypeOfColumn($last);
                if ($type == 'integer' || $type == 'string') {
                    $cache[$key]['isSimpleType'] = true;
                } else {
                    $cache[$key]['type']         = $type;
                    $cache[$key]['isSimpleType'] = false;
                }
            }

            $map       = $this->_queryComponents[$cache[$key]['dqlAlias']];
            $table     = $map['table'];
            $dqlAlias  = $cache[$key]['dqlAlias'];
            $fieldName = $cache[$key]['fieldName'];
            $agg       = false;
            if (isset($this->_queryComponents[$dqlAlias]['agg']) && isset($this->_queryComponents[$dqlAlias]['agg'][$fieldName])) {
                $fieldName = $this->_queryComponents[$dqlAlias]['agg'][$fieldName];
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
                $rowData[$this->_rootAlias][$fieldName] = $preparedValue;
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

    abstract public function registerCollection(Doctrine_Collection $coll): void;

    /** @param Item $record */
    abstract public function initRelated(&$record, string $name, ?string $keyColumn = null): bool;

    abstract public function getNullPointer(): ?Doctrine_Null;

    /**
     * @phpstan-param class-string<Doctrine_Record> $component
     * @return Item
     */
    abstract public function getElement(array $data, string $component);

    /** @param Collection $coll */
    abstract public function getLastKey(&$coll): mixed;

    /** @param null|Doctrine_Null|Collection $coll */
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
     * @phpstan-param class-string<Doctrine_Record> $component
     * @return string The name of the class to create
     */
    protected function _getClassnameToReturn(array &$data, $component): string
    {
        if (!isset($this->_tables[$component])) {
            $this->_tables[$component] = Doctrine_Core::getTable($component);
        }

        /** @phpstan-var class-string<Doctrine_Record>[] */
        $subclasses = $this->_tables[$component]->subclasses;
        if (!$subclasses) {
            return $component;
        }

        $matchedComponents = [$component];
        foreach ($subclasses as $subclass) {
            $table          = Doctrine_Core::getTable($subclass);
            $inheritanceMap = $table->inheritanceMap;
            $needMatches    = count($inheritanceMap);
            foreach ($inheritanceMap as $key => $value) {
                $key = $this->_tables[$component]->getFieldName($key);
                if (isset($data[$key]) && $data[$key] == $value) {
                    --$needMatches;
                }
            }
            if ($needMatches == 0) {
                $matchedComponents[] = $table->getComponentName();
            }
        }

        $matchedComponent = $matchedComponents[count($matchedComponents) - 1];

        if (!isset($this->_tables[$matchedComponent])) {
            $this->_tables[$matchedComponent] = Doctrine_Core::getTable($matchedComponent);
        }

        return $matchedComponent;
    }
}
