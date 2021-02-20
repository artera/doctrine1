<?php
class Phonenumber extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('phonenumber', 'string', 20);
        $this->hasColumn('entity_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasOne(
            'Entity',
            ['local'    => 'entity_id',
                                      'foreign'  => 'id',
            'onDelete' => 'CASCADE']
        );

        $this->hasOne(
            'Group',
            ['local'     => 'entity_id',
                                      'foreign'  => 'id',
            'onDelete' => 'CASCADE']
        );

        $this->hasOne(
            'User',
            ['local'    => 'entity_id',
                                    'foreign'  => 'id',
            'onDelete' => 'CASCADE']
        );
    }
}
