<?php
class GnatUserTable
{
}

class GnatUser extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 150);
        $this->hasColumn('foreign_id', 'integer', 10, ['unique' => true,]);
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('GnatEmail as Email', ['local' => 'foreign_id', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
    }
}
