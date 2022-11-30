<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class RegistryTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['User'];
    public static function prepareData(): void
    {
    }

    public function testAddingQueries()
    {
        $registry = new \Doctrine1\Query\Registry();

        $registry->add('all-users', 'SELECT u.* FROM User u');

        $this->assertEquals($registry->get('all-users')->getDql(), 'SELECT u.* FROM User u');
    }

    public function testAddingQueriesWithNamespaces()
    {
        $registry = new \Doctrine1\Query\Registry();

        $registry->add('User/all', 'SELECT u.* FROM User u');

        $this->assertEquals($registry->get('all', 'User')->getDql(), 'SELECT u.* FROM User u');

        static::$manager->setQueryRegistry($registry);

        $user = new \User();

        $user->getTable()->execute('all');
    }
}
