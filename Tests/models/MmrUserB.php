<?php
class MmrUserB extends Doctrine_Record
{
    public function setUp()
    {
        $this->hasMany(
            'MmrGroupB as Group',
            ['local' => 'user_id',
                                      'foreign'             => 'group_id',
            'refClass'            => 'MmrGroupUserB']
        );
    }

    public function setTableDefinition()
    {
        // Works when
        $this->hasColumn('id', 'string', 30, [  'primary' => true]);
        $this->hasColumn('name', 'string', 30);
    }
}
