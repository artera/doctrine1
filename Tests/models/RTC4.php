<?php
class RTC4 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('oid', 'integer', 11, ['autoincrement', 'primary']);
        $this->hasColumn('name', 'string', 20);
    }
    public function setUp(): void
    {
        $this->hasMany('M2MTest2', ['local' => 'c1_id', 'foreign' => 'c2_id', 'refClass' => 'JC3']);
    }
}
