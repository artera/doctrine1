<?php
class M2MTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('child_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasMany('RTC1 as RTC1', ['local' => 'c1_id', 'foreign' => 'c1_id', 'refClass' => 'JC1']);
        $this->hasMany('RTC2 as RTC2', ['local' => 'c1_id', 'foreign' => 'c1_id', 'refClass' => 'JC1']);
        $this->hasMany('RTC3 as RTC3', ['local' => 'c1_id', 'foreign' => 'c1_id', 'refClass' => 'JC2']);
        $this->hasMany('RTC3 as RTC4', ['local' => 'c1_id', 'foreign' => 'c1_id', 'refClass' => 'JC1']);
    }
}
