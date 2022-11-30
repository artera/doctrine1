<?php
class Package extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('description', 'string', 255);
    }

    public function setUp(): void
    {
        $this->hasMany('PackageVersion as Version', ['local' => 'id', 'foreign' => 'package_id', 'onDelete' => 'CASCADE']);
    }
}
