<?php

class Doctrine_Record_Filter_Compound extends Doctrine_Record_Filter
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

    public function filterSet(Doctrine_Record $record, $name, $value)
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
        throw new Doctrine_Record_UnknownPropertyException(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }

    public function filterGet(Doctrine_Record $record, $name)
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
        throw new Doctrine_Record_UnknownPropertyException(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }
}
