<?php

require_once 'Entity.php';

// grouptable doesn't extend Doctrine_Table -> Doctrine_Connection
// won't initialize grouptable when Doctrine_Connection->getTable('Group') is called
class GroupTable
{
}

class Group extends Entity
{
    public function setUp(): void
    {
        parent::setUp();
        $this->hasMany(
            'User',
            [
            'local'    => 'group_id',
            'foreign'  => 'user_id',
            'refClass' => 'GroupUser',
            ]
        );
    }
}
