<?php
class EntityAddress extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('user_id', 'integer', null, ['primary' => true]);
        $this->hasColumn('address_id', 'integer', null, ['primary' => true]);
    }
}
