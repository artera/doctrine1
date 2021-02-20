<?php
class UnderscoreColumn extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('_test_');
        $this->hasColumn('_underscore_', 'string', 255);
    }
}
