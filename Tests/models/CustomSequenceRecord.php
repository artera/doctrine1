<?php
class CustomSequenceRecord extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('id', 'integer', null, ['primary', 'sequence' => 'custom_seq']);
        $this->hasColumn('name', 'string');
    }
}
