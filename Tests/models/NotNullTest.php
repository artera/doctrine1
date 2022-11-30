<?php
class NotNullTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100, 'notnull');
        $this->hasColumn('type', 'integer', 11);
    }
}
