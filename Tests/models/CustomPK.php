<?php
class CustomPK extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('uid', 'integer', 11, 'autoincrement|primary');
        $this->hasColumn('name', 'string', 255);
    }
}
