<?php
/**
 * @property string $name
 * @property Doctrine_Collection<Record_City> $City
 */
class Record_Country extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp(): void
    {
        $this->hasMany(
            'Record_City as City',
            [
            'local'   => 'id',
            'foreign' => 'country_id'
            ]
        );
    }
}
