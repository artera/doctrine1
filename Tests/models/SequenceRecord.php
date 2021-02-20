<?php
class SequenceRecord extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('id', 'integer', null, ['primary', 'sequence']);
        $this->hasColumn('name', 'string');
    }
}
