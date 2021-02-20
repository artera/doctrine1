<?php
class PackageVersion extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('package_id', 'integer');
        $this->hasColumn('description', 'string', 255);
    }
    public function setUp(): void
    {
        $this->hasOne('Package', ['local' => 'package_id', 'foreign' => 'id']);
        $this->hasMany(
            'PackageVersionNotes as Note',
            [
            'local' => 'id', 'foreign' => 'package_version_id'
            ]
        );
    }
}
