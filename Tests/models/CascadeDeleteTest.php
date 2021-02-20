<?php
class CascadeDeleteTest extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
    }
    public function setUp(): void
    {
        $this->hasMany(
            'CascadeDeleteRelatedTest as Related',
            ['local'               => 'id',
                              'foreign' => 'cscd_id']
        );
    }
}
