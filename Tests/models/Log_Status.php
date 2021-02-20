<?php
class Log_Status extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 255);
    }
}
