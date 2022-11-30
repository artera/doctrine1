<?php
class MyGroup extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('my_group');

        $this->hasColumn('id', 'integer', 4, [  'primary' => true,  'autoincrement' => true,]);
        $this->hasColumn('name', 'string', 255, [  'notnull' => true,]);
        $this->hasColumn('description', 'string', 4000, []);
    }

    public function setUp(): void
    {
        $this->hasMany('MyUser as users', ['refClass' => 'MyUserGroup', 'local' => 'group_id', 'foreign' => 'user_id']);
    }
}
