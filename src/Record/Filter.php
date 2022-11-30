<?php

namespace Doctrine1\Record;

abstract class Filter
{
    protected \Doctrine1\Table $table;

    public function setTable(\Doctrine1\Table $table): void
    {
        $this->table = $table;
    }

    public function getTable(): \Doctrine1\Table
    {
        return $this->table;
    }

    public function init(): void
    {
    }

    /**
     * defines an implementation for filtering the set() method of \Doctrine1\Record
     *
     * @param  mixed $name  name of the property or related component
     * @param  mixed $value
     * @return \Doctrine1\Record
     */
    abstract public function filterSet(\Doctrine1\Record $record, $name, $value);

    /**
     * defines an implementation for filtering the get() method of \Doctrine1\Record
     *
     * @param  mixed $name name of the property or related component
     * @return mixed
     */
    abstract public function filterGet(\Doctrine1\Record $record, $name);
}
