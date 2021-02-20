<?php
class MigrationTest extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('field1', 'string');
    }
}
