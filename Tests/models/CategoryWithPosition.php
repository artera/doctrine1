<?php
class CategoryWithPosition extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('position', 'integer');
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp(): void
    {
        $this->hasMany('BoardWithPosition as Boards', ['local' => 'id' , 'foreign' => 'category_id']);
    }
}
