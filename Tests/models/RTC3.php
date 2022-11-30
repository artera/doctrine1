<?php
class RTC3 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp(): void
    {
        $this->hasMany('M2MTest as RTC3', ['local' => 'c1_id', 'foreign' => 'c2_id', 'refClass' => 'JC2']);
        $this->hasMany('M2MTest as RTC4', ['local' => 'c1_id', 'foreign' => 'c2_id', 'refClass' => 'JC1']);
    }
}
