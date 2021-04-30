<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1558Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine_Query::create()
            ->from('User u')
            ->whereIn('u.id', [1])
            ->orWhereIn('u.id', [1])
            ->orWhereIn('u.id', [1])
            ->andWhereIn('u.id', [1])
            ->whereIn('u.id', [])
            ->orWhereIn('u.id', []);
        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id IN (?) OR e.id IN (?) OR e.id IN (?) AND e.id IN (?) AND e.id IN (?) AND (e.type = 0))');
        $q->execute();
    }
}
