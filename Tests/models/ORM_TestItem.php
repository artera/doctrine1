<?php
class ORM_TestItem extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('test_items');
        $this->hasColumn('id', 'integer', 11, 'autoincrement|primary');
        $this->hasColumn('name', 'string', 255);
    }

    public function setUp(): void
    {
        $this->hasOne(
            'ORM_TestEntry',
            [
            'local' => 'id', 'foreign' => 'itemID'
            ]
        );
    }
}
