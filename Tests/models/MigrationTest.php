<?php
class MigrationTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('field1', 'string');
    }
}
