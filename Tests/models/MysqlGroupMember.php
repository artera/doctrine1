<?php
class MysqlGroupMember extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('group_id', 'integer', null, ['primary' => true]);
        $this->hasColumn('user_id', 'integer', null, ['primary' => true]);
    }
}
