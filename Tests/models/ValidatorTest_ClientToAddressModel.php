<?php
class ValidatorTest_ClientToAddressModel extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('client_id', 'integer', 11, ['primary' => true]);
        $this->hasColumn('address_id', 'integer', 11, ['primary' => true]);
    }

    public function construct()
    {
    }

    public function setUp(): void
    {
    }
}
