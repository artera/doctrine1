<?php
class TreeLeaf extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('parent_id', 'integer');
    }

    public function setUp(): void
    {
        $this->hasOne(
            'TreeLeaf as Parent',
            [
            'local' => 'parent_id', 'foreign' => 'id'
            ]
        );

        $this->hasMany(
            'TreeLeaf as Children',
            [
            'local' => 'id', 'foreign' => 'parent_id'
            ]
        );
    }
}
