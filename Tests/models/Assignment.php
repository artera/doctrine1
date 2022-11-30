<?php
class Assignment extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('task_id', 'integer');
        $this->hasColumn('resource_id', 'integer');
    }
}
