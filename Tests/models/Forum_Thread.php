<?php
class Forum_Thread extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('board_id', 'integer', 10);
        $this->hasColumn('updated', 'integer', 10);
        $this->hasColumn('closed', 'integer', 1);
    }
    public function setUp(): void
    {
        $this->hasOne('Forum_Board as Board', ['local' => 'board_id', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        $this->hasMany('Forum_Entry as Entries', ['local' => 'id', 'foreign' => 'thread_id']);
    }
}
