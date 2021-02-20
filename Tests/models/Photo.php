<?php
class Photo extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'Tag',
            [
            'local'    => 'photo_id',
            'foreign'  => 'tag_id',
            'refClass' => 'Phototag'
            ]
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
    }
}
