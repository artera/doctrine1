<?php
class Account extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('entity_id', 'integer');
        $this->hasColumn('amount', 'integer');
    }
}
