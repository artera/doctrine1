<?php
class Account extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('entity_id', 'integer');
        $this->hasColumn('amount', 'integer');
    }
}
