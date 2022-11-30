<?php
/**
 * @property string $name
 * @property \Doctrine1\Collection<Record_City> $City
 */
class Record_Country extends \Doctrine1\Record
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
