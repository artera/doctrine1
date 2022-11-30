<?php
class QueryTest_Subscription extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('id', 'integer', 4, ['primary', 'autoincrement', 'notnull']);
        $this->hasColumn('begin', 'date');
        $this->hasColumn('end', 'date');
    }
}
