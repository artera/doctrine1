<?php
class GnatEmail extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('address', 'string', 150);
    }
}
