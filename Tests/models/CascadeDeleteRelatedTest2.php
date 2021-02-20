<?php
class CascadeDeleteRelatedTest2 extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('cscd_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasOne(
            'CascadeDeleteRelatedTest',
            ['local'    => 'cscd_id',
                                                        'foreign'  => 'id',
            'onDelete' => 'SET NULL']
        );
    }
}
