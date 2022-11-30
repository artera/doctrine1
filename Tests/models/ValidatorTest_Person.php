<?php
class ValidatorTest_Person extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('identifier', 'integer', 4, ['notblank', 'unique']);
        $this->hasColumn('is_football_player', 'boolean');
    }

    public function setUp(): void
    {
        $this->hasOne(
            'ValidatorTest_FootballPlayer',
            [
            'local' => 'id', 'foreign' => 'person_id', 'onDelete' => 'CASCADE'
            ]
        );
    }
}
