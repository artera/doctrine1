<?php
class EnumTest2 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('status', 'enum', 11, ['values' => ['open', 'verified', 'closed']]);
        $this->hasColumn('enum_test_id', 'integer');
    }
}
