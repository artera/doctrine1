<?php
class ORM_AccessGroup extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp(): void
    {
        $this->hasMany(
            'ORM_AccessControl as accessControls',
            [
            'local' => 'accessGroupID', 'foreign' => 'accessControlID', 'refClass' => 'ORM_AccessControlsGroups'
            ]
        );
    }
}
