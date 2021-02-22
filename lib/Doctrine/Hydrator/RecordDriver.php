<?php

/** @extends Doctrine_Hydrator_Graph<Doctrine_Collection<Doctrine_Record>, Doctrine_Record> */
class Doctrine_Hydrator_RecordDriver extends Doctrine_Hydrator_Graph
{
    /**
     * @phpstan-var Doctrine_Collection[]
     */
    protected array $collections = [];

    /**
     * @phpstan-var array<string, bool>[]
     */
    private array $initializedRelations = [];

    /** @phpstan-param class-string<Doctrine_Record> $component */
    public function getElementCollection(string $component)
    {
        $coll = Doctrine_Collection::create($component);
        $this->collections[] = $coll;
        return $coll;
    }

    /**
     * @return true
     */
    public function initRelated(&$record, string $name, ?string $keyColumn = null): bool
    {
        if (!isset($this->initializedRelations[$record->getOid()][$name])) {
            $relation = $record->getTable()->getRelation($name);
            $coll = Doctrine_Collection::create($relation->getTable()->getComponentName(), $keyColumn);
            $coll->setReference($record, $relation);
            $record[$name]                                         = $coll;
            $this->initializedRelations[$record->getOid()][$name] = true;
        }
        return true;
    }

    public function registerCollection(Doctrine_Collection $coll): void
    {
        $this->collections[] = $coll;
    }

    public function getNullPointer(): ?Doctrine_Null
    {
        return Doctrine_Null::instance();
    }

    public function getElement(array $data, string $component)
    {
        $component = $this->_getClassnameToReturn($data, $component);

        $this->_tables[$component]->setData($data);
        $record = $this->_tables[$component]->getRecord();

        return $record;
    }

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
        foreach ($this->collections as $coll) {
            $coll->takeSnapshot();
        }
        $this->initializedRelations = [];
        $this->collections = [];
        $this->_tables = [];
    }
}
