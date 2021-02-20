<?php
class Song extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasOne(
            'Album',
            ['local'    => 'album_id',
                                     'foreign'  => 'id',
            'onDelete' => 'CASCADE']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('album_id', 'integer');
        $this->hasColumn('genre', 'string', 20);
        $this->hasColumn('title', 'string', 30);
    }
}
