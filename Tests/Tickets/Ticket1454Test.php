<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1454Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine_Query::create()
            ->from('User u')
            ->leftJoin('u.Phonenumber p')
            ->where('p.id = (SELECT MAX(p2.id) FROM Phonenumber p2 LIMIT 1)')
            ->orWhere('p.id = (SELECT MIN(p3.id) FROM Phonenumber p3 LIMIT 1)');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (p.id = (SELECT MAX(p2.id) AS p2__0 FROM phonenumber p2 LIMIT 1) OR p.id = (SELECT MIN(p3.id) AS p3__0 FROM phonenumber p3 LIMIT 1) AND (e.type = 0))');
    }
}
