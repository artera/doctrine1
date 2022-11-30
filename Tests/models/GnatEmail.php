<?php
class GnatEmail extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('address', 'string', 150);
    }
}
