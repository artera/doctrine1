<?php
class CPK_Test2 extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp(): void
    {
        $this->hasMany(
            'CPK_Test as Test',
            [
            'local'    => 'test2_id',
            'foreign'  => 'test1_id',
            'refClass' => 'CPK_Association'
            ]
        );
    }
}
