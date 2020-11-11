<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class GroupbyTest extends DoctrineUnitTestCase
{
    public function testAggregateFunctionsInHavingReturnValidSql()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.name, COUNT(p.id) count FROM User u LEFT JOIN u.Phonenumber p GROUP BY count');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, COUNT(p.id) AS p__0 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) GROUP BY p__0');
    }
}
