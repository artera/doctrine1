<?php
/** @property string $address */
class Email extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('address', 'string', 150, 'email|unique');
    }
}
