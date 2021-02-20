<?php
class DateTest extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('date', 'date', 20);
    }
}
