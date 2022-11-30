<?php
class Role extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 20, ['unique' => true]);
    }
    public function setUp(): void
    {
        $this->hasMany('Auth', ['local' => 'id', 'foreign' => 'roleid']);
    }
}
