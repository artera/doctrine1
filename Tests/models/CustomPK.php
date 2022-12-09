<?php

class CustomPK extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('uid', 'integer', 11, ['autoincrement' => true, 'primary' => true]);
        $this->hasColumn('name', 'string', 255);
    }
}
