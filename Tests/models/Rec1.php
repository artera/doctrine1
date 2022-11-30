<?php
class Rec1 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('first_name', 'string', 128, []);
    }

    public function setUp(): void
    {
        $this->hasOne('Rec2 as Account', ['local' => 'id', 'foreign' => 'user_id', 'onDelete' => 'CASCADE']);
    }
}
