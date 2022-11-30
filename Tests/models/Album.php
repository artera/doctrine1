<?php
class Album extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasMany('Song', ['local' => 'id', 'foreign' => 'album_id']);
        $this->hasOne(
            'User',
            ['local'    => 'user_id',
                                    'foreign'  => 'id',
            'onDelete' => 'CASCADE']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('name', 'string', 20);
    }
}
