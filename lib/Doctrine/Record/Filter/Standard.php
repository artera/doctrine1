<?php

class Doctrine_Record_Filter_Standard extends Doctrine_Record_Filter
{
    /**
     * defines an implementation for filtering the set() method of Doctrine_Record
     *
     * @param mixed $name  name of the property or related component
     * @param mixed $value
     * @return void
     */
    public function filterSet(Doctrine_Record $record, $name, $value)
    {
        throw new Doctrine_Record_UnknownPropertyException(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }

    /**
     * defines an implementation for filtering the get() method of Doctrine_Record
     *
     * @param mixed $name name of the property or related component
     *
     * @return void
     */
    public function filterGet(Doctrine_Record $record, $name)
    {
        throw new Doctrine_Record_UnknownPropertyException(sprintf('Unknown record property / related component "%s" on "%s"', $name, get_class($record)));
    }
}
