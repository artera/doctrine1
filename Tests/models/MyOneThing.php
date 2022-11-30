<?php
class MyOneThing extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('user_id', 'integer');
    }

    public function setUp(): void
    {
        $this->hasMany(
            'MyUserOneThing',
            [
            'local' => 'id', 'foreign' => 'one_thing_id'
            ]
        );

        $this->hasOne(
            'MyUser',
            [
            'local' => 'user_id', 'foreign' => 'id'
            ]
        );
    }
}
