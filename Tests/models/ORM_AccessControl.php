<?php
class ORM_AccessControl extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp(): void
    {
        $this->hasMany(
            'ORM_AccessGroup as accessGroups',
            [
            'local' => 'accessControlID', 'foreign' => 'accessGroupID', 'refClass' => 'ORM_AccessControlsGroups'
            ]
        );
    }
}
