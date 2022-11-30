<?php
class MyUser extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
    }

    public function setUp(): void
    {
        $this->hasMany(
            'MyOneThing',
            [
            'local' => 'id', 'foreign' => 'user_id'
            ]
        );

        $this->hasMany(
            'MyOtherThing',
            [
            'local' => 'id', 'foreign' => 'user_id'
            ]
        );
    }
}
