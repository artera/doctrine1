<?php
class ResourceType extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'Resource as ResourceAlias',
            ['local'    => 'type_id',
                                                          'foreign'  => 'resource_id',
            'refClass' => 'ResourceReference']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('type', 'string', 100);
    }
}
