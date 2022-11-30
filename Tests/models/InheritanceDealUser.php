<?php
class InheritanceEntityUser extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('inheritance_entity_user');

        $this->hasColumn('type', 'integer', 4, [  'primary' => true,]);
        $this->hasColumn('user_id', 'integer', 4, [  'primary' => true,]);
        $this->hasColumn('entity_id', 'integer', 4, [  'primary' => true,]);
    }

    public function setUp(): void
    {
    }
}

class InheritanceDealUser extends InheritanceEntityUser
{
    public function setTableDefinition(): void
    {
        parent::setTableDefinition();

        $this->setTableName('inheritance_entity_user');

        $this->hasColumn('user_id', 'integer', 4, [  'primary' => true,]);
        $this->hasColumn('entity_id', 'integer', 4, [  'primary' => true,]);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->hasOne('InheritanceUser as User', ['local' => 'user_id', 'foreign' => 'id']);
        $this->hasOne('InheritanceDeal as Deal', ['local' => 'entity_id', 'foreign' => 'id']);
        $this->setInheritanceMap(
            [
            'type' => 1,
            ]
        );
    }
}
