<?php
class InheritanceDeal extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('inheritance_deal');

        $this->hasColumn('id', 'integer', 4, [  'primary' => true,  'autoincrement' => true,]);
        $this->hasColumn('name', 'string', 255, []);
    }

    public function setUp(): void
    {
        $this->hasMany('InheritanceUser as Users', ['refClass' => 'InheritanceDealUser', 'local' => 'entity_id', 'foreign' => 'user_id']);
    }
}
