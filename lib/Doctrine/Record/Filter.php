<?php

abstract class Doctrine_Record_Filter
{
    protected Doctrine_Table $table;

    public function setTable(Doctrine_Table $table): void
    {
        $this->table = $table;
    }

    public function getTable(): Doctrine_Table
    {
        return $this->table;
    }

    public function init(): void
    {
    }

    /**
     * defines an implementation for filtering the set() method of Doctrine_Record
     *
     * @param  mixed $name  name of the property or related component
     * @param  mixed $value
     * @return Doctrine_Record
     */
    abstract public function filterSet(Doctrine_Record $record, $name, $value);

    /**
     * defines an implementation for filtering the get() method of Doctrine_Record
     *
     * @param  mixed $name name of the property or related component
     * @return mixed
     */
    abstract public function filterGet(Doctrine_Record $record, $name);
}
