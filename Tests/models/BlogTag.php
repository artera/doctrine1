<?php
class BlogTag extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('description', 'string');
    }
    public function setUp(): void
    {
        $this->hasOne('Blog', ['onDelete' => 'CASCADE']);
    }
}
