<?php
class SelfRefTest extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 50);
        $this->hasColumn('created_by', 'integer');
    }
    public function setUp(): void
    {
        $this->hasOne('SelfRefTest as createdBy', ['local' => 'created_by']);
    }
}
