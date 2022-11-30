<?php

class MmrGroupUserC extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('user_id', 'string', 30, ['primary' => true]);
        $this->hasColumn('group_id', 'string', 30, ['primary' => true]);
    }
}
