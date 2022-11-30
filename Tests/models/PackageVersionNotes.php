<?php
class PackageVersionNotes extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('package_version_id', 'integer');
        $this->hasColumn('description', 'string', 255);
    }
    public function setUp(): void
    {
        $this->hasOne(
            'PackageVersion',
            [
            'local' => 'package_version_id', 'foreign' => 'id'
            ]
        );
    }
}
