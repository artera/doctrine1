<?php
class FilterTest2 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('test1_id', 'integer');
    }
}
