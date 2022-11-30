<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class NotExistsTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['User', 'Group', 'GroupUser'];

    public static function prepareData(): void
    {
    }

    public function testInitData()
    {
    }

    public function testQueryDoesNotSeparateNotAndExists()
    {
        $q = new \Doctrine1\Query();

        $q->select('u.id')
            ->from('User u')
            ->where('NOT EXISTS (SELECT g.id FROM Group g)');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id FROM entity e WHERE (NOT EXISTS (SELECT e2.id AS e2__id FROM entity e2 WHERE (e2.type = 1)) AND (e.type = 0))');
    }
}
