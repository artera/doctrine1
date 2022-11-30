<?php
class NestReference extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('parent_id', 'integer', 4, 'primary');
        $this->hasColumn('child_id', 'integer', 4, 'primary');
    }
}
