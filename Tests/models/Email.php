<?php

/** @property string $address */
class Email extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('address', 'string', 150, ['email' => true, 'unique' => true]);
    }
}
