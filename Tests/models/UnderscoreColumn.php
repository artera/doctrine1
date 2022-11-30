<?php
class UnderscoreColumn extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('_test_');
        $this->hasColumn('_underscore_', 'string', 255);
    }
}
