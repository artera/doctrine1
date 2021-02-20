<?php

/** @extends Doctrine_Hydrator_Graph<Doctrine_Collection> */
class Doctrine_Hydrator_RecordDriver extends Doctrine_Hydrator_Graph
{
    /**
     * @var array|null
     */
    protected $_collections = [];

    /**
     * @var array|null
     */
    private $_initializedRelations = [];

    public function getElementCollection(string $component): Doctrine_Collection
    {
        $coll                 = Doctrine_Collection::create($component);
        $this->_collections[] = $coll;

        return $coll;
    }

    /**
     * @param  Doctrine_Record $record
     * @param  string          $name
     * @param  string          $keyColumn
     * @return true
     */
    public function initRelated(&$record, $name, $keyColumn = null)
    {
        if (!isset($this->_initializedRelations[$record->getOid()][$name])) {
            $relation = $record->getTable()->getRelation($name);
            $coll     = Doctrine_Collection::create($relation->getTable()->getComponentName(), $keyColumn);
            $coll->setReference($record, $relation);
            $record[$name]                                         = $coll;
            $this->_initializedRelations[$record->getOid()][$name] = true;
        }
        return true;
    }

    public function registerCollection(Doctrine_Collection $coll): void
    {
        $this->_collections[] = $coll;
    }

    public function getNullPointer(): ?Doctrine_Null
    {
        return Doctrine_Null::instance();
    }

    /**
     * @param  string $component
     * @return Doctrine_Record
     */
    public function getElement(array $data, $component)
    {
        $component = $this->_getClassnameToReturn($data, $component);

        $this->_tables[$component]->setData($data);
        $record = $this->_tables[$component]->getRecord();

        return $record;
    }

    /**
     * @param  Doctrine_Collection $coll
     * @return mixed
     */
    public function getLastKey(&$coll): mixed
    {
        $coll->end();
        return $coll->key();
    }

    public function setLastElement(array &$prev, &$coll, int|bool $index, string $dqlAlias, bool $oneToOne): void
    {
        if ($coll instanceof Doctrine_Null || $coll === null) {
            unset($prev[$dqlAlias]); // Ticket #1228
            return;
        }

        if ($index !== false) {
            // Link element at $index to previous element for the component
            // identified by the DQL alias $alias
            $prev[$dqlAlias] = $coll[$index];
            return;
        }

        if (count($coll) > 0) {
            $prev[$dqlAlias] = $coll->getLast();
        }
    }

    public function flush(): void
    {
        // take snapshots from all initialized collections
        foreach ($this->_collections as $key => $coll) {
            $coll->takeSnapshot();
        }
        $this->_initializedRelations = null;
        $this->_collections          = null;
        $this->_tables               = null;
    }
}
