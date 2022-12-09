<?php

namespace Doctrine1\Hydrator;

use Doctrine1\Collection;
use Doctrine1\None;

/** @extends \Doctrine1\Hydrator\Graph<\Doctrine1\Collection<\Doctrine1\Record>, \Doctrine1\Record> */
class RecordDriver extends \Doctrine1\Hydrator\Graph
{
    /**
     * @phpstan-var \Doctrine1\Collection[]
     */
    protected array $collections = [];

    /**
     * @phpstan-var array<string, bool>[]
     */
    private array $initializedRelations = [];

    /** @phpstan-param class-string<\Doctrine1\Record> $component */
    public function getElementCollection(string $component)
    {
        $coll = Collection::create($component);
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
            $coll = Collection::create($relation->getTable()->getComponentName(), $keyColumn);
            $coll->setReference($record, $relation);
            $record[$name]                                         = $coll;
            $this->initializedRelations[$record->getOid()][$name] = true;
        }
        return true;
    }

    public function registerCollection(Collection $coll): void
    {
        $this->collections[] = $coll;
    }

    public function getNullPointer(): ?None
    {
        return None::instance();
    }

    public function getElement(array $data, string $component)
    {
        $component = $this->getClassnameToReturn($data, $component);

        $this->tables[$component]->setData($data);
        $record = $this->tables[$component]->getRecord();

        return $record;
    }

    public function getLastKey(&$coll): mixed
    {
        $coll->end();
        return $coll->key();
    }

    public function setLastElement(array &$prev, &$coll, int|bool $index, string $dqlAlias, bool $oneToOne): void
    {
        if ($coll instanceof None || $coll === null) {
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
        $this->tables = [];
    }
}
