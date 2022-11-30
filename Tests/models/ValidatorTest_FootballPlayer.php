<?php
class ValidatorTest_FootballPlayer extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('person_id', 'string', 255);
        $this->hasColumn('team_name', 'string', 255);
        $this->hasColumn('goals_count', 'integer', 4);
    }
}
