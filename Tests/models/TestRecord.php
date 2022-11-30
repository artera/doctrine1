<?php
class TestRecord extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('test');
    }
}
