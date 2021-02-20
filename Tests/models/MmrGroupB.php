<?php
class MmrGroupB extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'MmrUserB',
            ['local' => 'group_id',
                                     'foreign'    => 'user_id',
            'refClass'   => 'MmrGroupUserB']
        );
    }
    public function setTableDefinition(): void
    {
        // Works when
        $this->hasColumn('id', 'string', 30, [  'primary' => true]);
        $this->hasColumn('name', 'string', 30);
    }
}
