<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class HavingTest extends DoctrineUnitTestCase
{
    public function testAggregateFunctionsInHavingReturnValidSql()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p HAVING COUNT(p.id) > 2');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) HAVING COUNT(p.id) > 2');
    }
    public function testAggregateFunctionsInHavingReturnValidSql2()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery("SELECT u.name FROM User u LEFT JOIN u.Phonenumber p HAVING MAX(u.name) = 'zYne'");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) HAVING MAX(e.name) = 'zYne'");
    }

    public function testAggregateFunctionsInHavingSupportMultipleParameters()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery("SELECT CONCAT(u.name, u.loginname) name FROM User u LEFT JOIN u.Phonenumber p HAVING name = 'xx'");
    }

    public function testReturnFuncIfNumeric()
    {
        $having = new \Doctrine1\Query\Having(new \Doctrine1\Query());
        $part   = $having->load('1');
        $this->assertEquals('1', trim($part));
    }
}
