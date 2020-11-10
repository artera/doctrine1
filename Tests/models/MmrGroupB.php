<?php
class MmrGroupB extends Doctrine_Record
{
    public function setUp()
    {
        $this->hasMany(
            'MmrUserB',
            ['local' => 'group_id',
                                     'foreign'    => 'user_id',
            'refClass'   => 'MmrGroupUserB']
        );
    }
    public function setTableDefinition()
    {
        // Works when
        $this->hasColumn('id', 'string', 30, [  'primary' => true]);
        $this->hasColumn('name', 'string', 30);
    }
}
