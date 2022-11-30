<?php
class Bookmark extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('user_id', 'integer', null, ['primary' => true]);
        $this->hasColumn('page_id', 'integer', null, ['primary' => true]);
    }
}
