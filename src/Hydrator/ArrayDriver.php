<?php

namespace Doctrine1\Hydrator;

/** @phpstan-extends \Doctrine1\Hydrator\Graph<array, array> */
class ArrayDriver extends \Doctrine1\Hydrator\Graph
{
    public function getElementCollection(string $component): array
    {
        return [];
    }

    public function getElement(array $data, string $component)
    {
        return $data;
    }

    public function registerCollection(\Doctrine1\Collection $coll): void
    {
    }

    /**
     * @return true
     */
    public function initRelated(&$record, string $name, ?string $keyColumn = null): bool
    {
        if (!isset($record[$name])) {
            $record[$name] = [];
        }
        return true;
    }

    public function getNullPointer(): ?\Doctrine1\None
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
        if ($coll instanceof \Doctrine1\None || $coll === null) {
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
