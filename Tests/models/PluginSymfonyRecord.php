<?php
require_once 'BaseSymfonyRecord.php';

abstract class PluginSymfonyRecord extends BaseSymfonyRecord
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function setTableDefinition(): void
    {
        parent::setTableDefinition();
    }
}
