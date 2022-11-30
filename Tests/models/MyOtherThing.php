<?php
class MyOtherThing extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('user_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasMany(
            'MyUserOtherThing',
            [
            'local' => 'id', 'foreign' => 'other_thing_id'
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
