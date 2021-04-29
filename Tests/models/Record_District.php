<?php
/** @property string $name */
class Record_District extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
    }
}
