<?php
class CascadeDeleteRelatedTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('cscd_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasOne(
            'CascadeDeleteTest',
            ['local'    => 'cscd_id',
                                                 'foreign'  => 'id',
                                                 'onDelete' => 'CASCADE',
            'onUpdate' => 'SET NULL']
        );

        $this->hasMany(
            'CascadeDeleteRelatedTest2 as Related',
            ['local'               => 'id',
                              'foreign' => 'cscd_id']
        );
    }
}
