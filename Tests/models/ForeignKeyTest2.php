<?php
class ForeignKeyTest2 extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', null);
        $this->hasColumn('foreignkey', 'integer');

        $this->hasOne(
            'ForeignKeyTest',
            [
            'local' => 'foreignKey', 'foreign' => 'id'
            ]
        );
    }
}
