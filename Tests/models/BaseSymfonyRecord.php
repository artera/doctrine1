<?php
abstract class BaseSymfonyRecord extends \Doctrine1\Record
{
    public function setUp(): void
    {
    }

    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 30);
    }
}
