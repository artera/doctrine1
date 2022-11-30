<?php
class Tag extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'Photo',
            [
            'local'    => 'tag_id',
            'foreign'  => 'photo_id',
            'refClass' => 'Phototag'
            ]
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('tag', 'string', 100);
    }
}
