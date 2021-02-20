<?php
class Forum_Entry extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('author', 'string', 50);
        $this->hasColumn('topic', 'string', 100);
        $this->hasColumn('message', 'string', 99999);
        $this->hasColumn('parent_entry_id', 'integer', 10);
        $this->hasColumn('thread_id', 'integer', 10);
        $this->hasColumn('date', 'integer', 10);
    }
    public function setUp(): void
    {
        $this->hasOne('Forum_Entry as Parent', ['local' => 'id', 'foreign' => 'parent_entry_id']);
        $this->hasOne('Forum_Thread as Thread', ['local' => 'thread_id', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
    }
}
