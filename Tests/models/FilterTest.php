<?php
class FilterTest extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
    }
    public function setUp(): void
    {
        $this->hasMany('FilterTest2 as filtered', ['local' => 'id', 'foreign' => 'test1_id', 'onDelete' => 'CASCADE']);
    }
}
