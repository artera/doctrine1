<?php
class Task extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'Resource as ResourceAlias',
            ['local'  => 'task_id',
                                                        'foreign'  => 'resource_id',
            'refClass' => 'Assignment']
        );
        $this->hasMany('Task as Subtask', ['local' => 'id', 'foreign' => 'parent_id']);
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('parent_id', 'integer');
    }
}
