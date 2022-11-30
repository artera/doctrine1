<?php
class Phototag extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('photo_id', 'integer', 11, ['primary' => true]);
        $this->hasColumn('tag_id', 'integer', 11, ['primary' => true]);
    }
}
