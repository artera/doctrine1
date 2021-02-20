<?php
class BookmarkUser extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'Bookmark as Bookmarks',
            ['local'               => 'id',
                              'foreign' => 'user_id']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 30);
    }
}
