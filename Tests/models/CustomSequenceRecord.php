<?php
class CustomSequenceRecord extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('id', 'integer', null, ['primary', 'sequence' => 'custom_seq']);
        $this->hasColumn('name', 'string');
    }
}
