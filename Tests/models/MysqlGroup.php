<?php
class MysqlGroup extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', null);
    }

    public function setUp(): void
    {
        $this->hasMany(
            'MysqlUser',
            [
            'local'    => 'group_id',
            'foreign'  => 'user_id',
            'refClass' => 'MysqlGroupMember'
            ]
        );
    }
}
