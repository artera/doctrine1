<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1400Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine_Query::create()
            ->from('User u')
            ->where('u.id IN (SELECT u2.id FROM User u2 GROUP BY u2.id HAVING MAX(u2.version))')
            ->orderBy('u.loginname asc');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id IN (SELECT e2.id AS e2__id FROM entity e2 WHERE (e2.type = 0) GROUP BY e2.id HAVING MAX(e2.version)  ) AND (e.type = 0)) ORDER BY e.loginname asc');
    }
}
