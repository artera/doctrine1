<?php
class Address extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'User',
            ['local'    => 'address_id',
                                     'foreign'  => 'user_id',
            'refClass' => 'EntityAddress']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('address', 'string', 200);
    }
}
