<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class GroupbyTest extends DoctrineUnitTestCase
{
    public function testAggregateFunctionsInHavingReturnValidSql(): void
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.name, COUNT(p.id) count FROM User u LEFT JOIN u.Phonenumber p GROUP BY count');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, COUNT(p.id) AS p__0 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) GROUP BY p__0');
    }

    public function testGroupByParams(): void
    {
        $q = new \Doctrine_Query();

        $q->from('User u');
        $q->groupBy('u.name');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0) GROUP BY e.name');

        $q->addGroupBy('u.loginname LIKE ?', 'A%');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0) GROUP BY e.name, e.loginname LIKE ?');
    }
}
