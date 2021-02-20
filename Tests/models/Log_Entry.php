<?php
class Log_Entry extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('stamp', 'timestamp');
        $this->hasColumn('status_id', 'integer');
    }

    public function setUp(): void
    {
        $this->hasOne(
            'Log_Status',
            [
            'local' => 'status_id', 'foreign' => 'id'
            ]
        );
    }
}
