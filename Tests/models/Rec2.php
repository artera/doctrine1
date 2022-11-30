<?php
class Rec2 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('user_id', 'integer', 10, [  'unique' => true,]);
        $this->hasColumn('address', 'string', 150, []);
    }

    public function setUp(): void
    {
        $this->hasOne('Rec1 as User', ['local' => 'id', 'foreign' => 'user_id', 'onDelete' => 'CASCADE']);
    }
}
