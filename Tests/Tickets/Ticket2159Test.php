<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2159Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine1\Core::getTable('User')
          ->createQuery('u');

        $sql = 'SELECT COUNT(*) AS num_results FROM entity e WHERE (e.type = 0)';
        $this->assertEquals($q->getCountSqlQuery(), $sql);
        $results1 = $q->execute();
        $count1   = $q->count();
        $this->assertEquals($q->getCountSqlQuery(), $sql);
        $results2 = $q->execute();
        $count2   = $q->count();
        $this->assertEquals($results1, $results2);
        $this->assertEquals($count1, $count2);

        $q->leftJoin('u.Phonenumber p');
        $sql = $q->getSqlQuery();
        $this->assertEquals($sql, 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
        $results3 = $q->execute();
        $this->assertEquals($results2, $results3);
        $count3 = $q->count();
        $this->assertEquals($count2, $count3);
    }
}
