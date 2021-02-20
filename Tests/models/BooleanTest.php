<?php
class BooleanTest extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('is_working', 'boolean');
        $this->hasColumn('is_working_notnull', 'boolean', 1, ['default' => false, 'notnull' => true]);
    }
}
