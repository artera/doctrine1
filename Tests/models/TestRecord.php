<?php
class TestRecord extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('test');
    }
}
