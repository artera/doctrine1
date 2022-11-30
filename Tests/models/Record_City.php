<?php
class Record_City extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('country_id', 'integer', null, ['notnull' => true]);
        $this->hasColumn('district_id', 'integer');
    }

    public function setUp(): void
    {
        $this->hasOne(
            'Record_Country as Country',
            [
            'local' => 'country_id', 'foreign' => 'id'
            ]
        );

        $this->hasOne(
            'Record_District as District',
            [
            'local' => 'district_id', 'foreign' => 'id'
            ]
        );
    }
}
