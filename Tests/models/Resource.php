<?php
class Resource extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'Task as TaskAlias',
            ['local'  => 'resource_id',
                                                'foreign'  => 'task_id',
            'refClass' => 'Assignment']
        );
        $this->hasMany(
            'ResourceType as Type',
            ['local'  => 'resource_id',
                                                   'foreign'  => 'type_id',
            'refClass' => 'ResourceReference']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
    }
}
