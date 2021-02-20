<?php
class MmrGroupC extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'MmrUserC',
            ['local'    => 'group_id',
                                          'foreign'  => 'user_id',
            'refClass' => 'MmrGroupUserC']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('g_id as id', 'string', 30, ['primary' => true]);
        $this->hasColumn('name', 'string', 30);
    }
}
