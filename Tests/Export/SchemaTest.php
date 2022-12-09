<?php

namespace Tests\Export;

use Tests\DoctrineUnitTestCase;

class SchemaTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['Entity',
                      'EntityReference',
                      'EntityAddress',
                      'Email',
                      'Phonenumber',
                      'GroupUser',
                      'Group',
                      'User',
                      'Album',
                      'Song',
                      'Element',
                      'TestError',
                      'Description',
                      'Address',
                      'Account',
                      'Task',
                      'Resource',
                      'Assignment',
                      'ResourceType',
                      'ResourceReference'];

    public function testYmlExport()
    {
        $export = new \Doctrine1\Export\Schema();
        $export->exportSchema('schema-export.yml', 'yml', dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'models', static::$tables);
        unlink('schema-export.yml');
    }
}
