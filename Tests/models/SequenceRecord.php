<?php
class SequenceRecord extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('id', 'integer', null, ['primary', 'sequence']);
        $this->hasColumn('name', 'string');
    }
}
