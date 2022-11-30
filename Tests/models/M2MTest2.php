<?php
class M2MTest2 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('oid', 'integer', 11, ['autoincrement' => true, 'primary' => true]);
        $this->hasColumn('name', 'string', 20);
    }
    public function setUp(): void
    {
        $this->hasMany('RTC4 as RTC5', ['local' => 'c1_id', 'foreign' => 'c1_id', 'refClass' => 'JC3']);
    }
}
