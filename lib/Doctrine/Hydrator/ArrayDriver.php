<?php

/** @extends Doctrine_Hydrator_Graph<array> */
class Doctrine_Hydrator_ArrayDriver extends Doctrine_Hydrator_Graph
{
    public function getElementCollection(string $component): array
    {
        return [];
    }

    /**
     * @param  string $component
     * @return array
     */
    public function getElement(array $data, $component)
    {
        return $data;
    }

    public function registerCollection(Doctrine_Collection $coll): void
    {
    }

    /**
     * @param  Doctrine_Record $record
     * @param  string          $name
     * @param  string          $keyColumn
     * @return true
     */
    public function initRelated(&$record, $name, $keyColumn = null)
    {
        if (!isset($record[$name])) {
            $record[$name] = [];
        }
        return true;
    }

    public function getNullPointer(): ?Doctrine_Null
    {
        return null;
    }

    public function getLastKey(&$coll): mixed
    {
        end($coll);
        return key($coll);
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
            $prev[$dqlAlias] = & $coll[$index];
            return;
        }

        if ($coll) {
            if ($oneToOne) {
                $prev[$dqlAlias] = &$coll;
            } else {
                end($coll);
                $prev[$dqlAlias] = &$coll[key($coll)];
            }
        }
    }
}
