<?php
class CPK_Association extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('test1_id', 'integer', 11, ['primary' => true]);
        $this->hasColumn('test2_id', 'integer', 11, ['primary' => true]);
    }
}
