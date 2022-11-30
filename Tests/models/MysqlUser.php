<?php
class MysqlUser extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', null);
    }

    public function setUp(): void
    {
        $this->hasMany(
            'MysqlGroup',
            [
            'local'    => 'user_id',
            'foreign'  => 'group_id',
            'refClass' => 'MysqlGroupMember'
            ]
        );
    }
}
