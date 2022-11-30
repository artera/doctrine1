<?php
class NestTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
    }
    public function setUp(): void
    {
        $this->hasMany(
            'NestTest as Parents',
            ['local'    => 'child_id',
                                                    'refClass' => 'NestReference',
            'foreign'  => 'parent_id']
        );
        $this->hasMany(
            'NestTest as Children',
            ['local'    => 'parent_id',
                                                     'refClass' => 'NestReference',
            'foreign'  => 'child_id']
        );

        $this->hasMany(
            'NestTest as Relatives',
            ['local'    => 'child_id',
                                                      'refClass' => 'NestReference',
                                                      'foreign'  => 'parent_id',
            'equal'    => true]
        );
    }
}
