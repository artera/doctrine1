<?php
class InheritanceUser extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('inheritance_user');

        $this->hasColumn('id', 'integer', 4, [  'primary' => true,  'autoincrement' => true,]);
        $this->hasColumn('username', 'string', 128, [  'notnull' => true,]);
    }

    public function setUp(): void
    {
        $this->hasMany('InheritanceDeal as Deals', ['refClass' => 'InheritanceDealUser', 'local' => 'user_id', 'foreign' => 'entity_id']);
    }
}
