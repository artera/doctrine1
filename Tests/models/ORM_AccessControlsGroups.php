<?php
class ORM_AccessControlsGroups extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('accessControlID', 'integer', 11, ['primary' => true]);
        $this->hasColumn('accessGroupID', 'integer', 11, ['primary' => true]);
    }
}
