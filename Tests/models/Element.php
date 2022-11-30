<?php
class Element extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('parent_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasMany(
            'Element as Child',
            ['local'   => 'id',
            'foreign' => 'parent_id']
        );
        $this->hasOne(
            'Element as Parent',
            ['local'   => 'parent_id',
            'foreign' => 'id']
        );
    }
}
