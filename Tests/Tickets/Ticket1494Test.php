<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1494Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine1\Query::create()
            ->select('u.*, CONCAT(u.id, u.name) as custom')
            ->from('User u INDEXBY custom');
        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, CONCAT(e.id, e.name) AS e__0 FROM entity e WHERE (e.type = 0)');
        $results = $q->fetchArray();
        $this->assertEquals($results['4zYne']['name'], 'zYne');
    }
}
