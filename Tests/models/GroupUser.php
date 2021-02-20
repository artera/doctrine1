<?php
class GroupUser extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('added', 'integer');
        $this->hasColumn('group_id', 'integer');
        $this->hasColumn('user_id', 'integer');
    }

    public function setUp(): void
    {
        $this->hasOne('Group', ['local' => 'group_id', 'foreign' => 'id']);
        $this->hasOne('User', ['local' => 'user_id', 'foreign' => 'id']);
    }
}
