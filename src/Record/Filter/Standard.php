<?php

namespace Doctrine1\Record\Filter;

class Standard extends \Doctrine1\Record\Filter
{
    /**
     * defines an implementation for filtering the set() method of \Doctrine1\Record
     *
     * @param mixed $name  name of the property or related component
     * @param mixed $value
     * @return void
     */
    public function filterSet(\Doctrine1\Record $record, $name, $value)
    {
        throw new \Doctrine1\Record\UnknownPropertyException(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }

    /**
     * defines an implementation for filtering the get() method of \Doctrine1\Record
     *
     * @param mixed $name name of the property or related component
     *
     * @return void
     */
    public function filterGet(\Doctrine1\Record $record, $name)
    {
        throw new \Doctrine1\Record\UnknownPropertyException(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }
}
