<?php
class DC600Cache extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('dc600_cache');
        $this->hasColumn(
            'id',
            'string',
            64,
            [
             'type'          => 'string',
             'fixed'         => 0,
             'unsigned'      => false,
             'primary'       => true,
             'autoincrement' => false,
             'length'        => '64',
            ]
        );
        $this->hasColumn(
            'data',
            'string',
            3000,
            [
             'type'          => 'string',
             'fixed'         => 0,
             'unsigned'      => false,
             'primary'       => false,
             'autoincrement' => false,
             'length'        => '3000',
            ]
        );
        $this->hasColumn(
            'expire',
            'timestamp',
            25,
            [
             'type'          => 'timestamp',
             'fixed'         => 0,
             'unsigned'      => false,
             'primary'       => false,
             'notnull'       => false,
             'autoincrement' => false,
             'length'        => '25',
            ]
        );
    }

    public function setUp(): void
    {
        parent::setUp();
    }
}
