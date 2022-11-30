<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1211Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $orig = static::$conn->getAttribute(\Doctrine1\Core::ATTR_PORTABILITY);
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_PORTABILITY, \Doctrine1\Core::PORTABILITY_NONE);

        $q = \Doctrine1\Query::create()
            ->select('u.*, COS(12.34) as test')
            ->from('User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, COS(12.34) AS e__0 FROM entity e WHERE (e.type = 0)');

        static::$conn->setAttribute(\Doctrine1\Core::ATTR_PORTABILITY, $orig);
    }
}
