<?php
class Page extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'Bookmark as Bookmarks',
            ['local'               => 'id',
                              'foreign' => 'page_id']
        );
    }

    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 30);
        $this->hasColumn('url', 'string', 100);
    }
}
