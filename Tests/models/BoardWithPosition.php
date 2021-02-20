<?php
class BoardWithPosition extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('position', 'integer');
        $this->hasColumn('category_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasOne('CategoryWithPosition as Category', ['local' => 'category_id', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
    }
}
