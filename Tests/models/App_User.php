<?php
class App_User extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('first_name', 'string', 32);
        $this->hasColumn('last_name', 'string', 32);
        $this->hasColumn('email', 'string', 128, 'email');
        $this->hasColumn('username', 'string', 16, 'unique, nospace');
        $this->hasColumn('password', 'string', 128, 'notblank');
        $this->hasColumn('country', 'string', 2, 'country');
        $this->hasColumn('zipcode', 'string', 9, 'nospace');
    }
    public function setUp(): void
    {
        $this->hasMany(
            'App',
            [
            'local'   => 'id',
            'foreign' => 'user_id'
            ]
        );
    }
}
