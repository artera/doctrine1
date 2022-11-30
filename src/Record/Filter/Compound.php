<?php

namespace Doctrine1\Record\Filter;

class Compound extends \Doctrine1\Record\Filter
{
    protected array $aliases = [];

    public function __construct(array $aliases)
    {
        $this->aliases = $aliases;
    }

    public function init(): void
    {
        // check that all aliases exist
        foreach ($this->aliases as $alias) {
            $this->table->getRelation($alias);
        }
    }

    public function filterSet(\Doctrine1\Record $record, $name, $value)
    {
        foreach ($this->aliases as $alias) {
            $relation = $record[$alias];

            if (!$record->exists()) {
                if (isset($relation[$name])) {
                    $relation[$name] = $value;

                    return $record;
                }
            } else {
                if (isset($relation[$name])) {
                    $relation[$name] = $value;
                }

                return $record;
            }
        }
        throw new \Doctrine1\Record\UnknownPropertyException(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }

    public function filterGet(\Doctrine1\Record $record, $name)
    {
        foreach ($this->aliases as $alias) {
            if (!$record->exists()) {
                if (isset($record[$alias][$name])) {
                    return $record[$alias][$name];
                }
            } else {
                if (isset($record[$alias][$name])) {
                    return $record[$alias][$name];
                }
            }
        }
        throw new \Doctrine1\Record\UnknownPropertyException(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }
}
