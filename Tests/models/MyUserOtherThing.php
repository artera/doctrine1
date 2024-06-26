<?php
class MyUserOtherThing extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('other_thing_id', 'integer');
    }


    public function setUp(): void
    {
        $this->hasOne(
            'MyUser',
            [
            'local' => 'user_id', 'foreign' => 'id'
            ]
        );

        $this->hasOne(
            'MyOtherThing',
            [
            'local' => 'other_thing_id', 'foreign' => 'id'
            ]
        );
    }
}
