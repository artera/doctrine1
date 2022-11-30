<?php
class Forum_Board extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('category_id', 'integer', 10);
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('description', 'string', 5000);
    }
    public function setUp(): void
    {
        $this->hasOne('Forum_Category as Category', ['local' => 'category_id', 'foreign' => 'id']);
        $this->hasMany('Forum_Thread as Threads', ['local' => 'id', 'foreign' => 'board_id']);
    }
}
