<?php
class DateTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('date', 'date', 20);
    }
}
