<?php
class ORM_AccessControlsGroups extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('accessControlID', 'integer', 11, ['primary' => true]);
        $this->hasColumn('accessGroupID', 'integer', 11, ['primary' => true]);
    }
}
