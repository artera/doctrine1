<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class JoinConditionTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public static function prepareTables(): void
    {
    }

    public function testJoinConditionsAreSupportedForOneToManyLeftJoins()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery("SELECT u.name, p.id FROM User u LEFT JOIN u.Phonenumber p ON p.phonenumber = '123 123'");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON (p.phonenumber = '123 123') WHERE (e.type = 0)");
    }

    public function testJoinConditionsAreSupportedForOneToManyInnerJoins()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery("SELECT u.name, p.id FROM User u INNER JOIN u.Phonenumber p ON p.phonenumber = '123 123'");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id FROM entity e INNER JOIN phonenumber p ON (p.phonenumber = '123 123') WHERE (e.type = 0)");
    }

    public function testJoinConditionsAreSupportedForManyToManyLeftJoins()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery('SELECT u.name, g.id FROM User u LEFT JOIN u.Group g ON g.id > 2');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e2.id AS e2__id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON (e2.id > 2) AND e2.type = 1 WHERE (e.type = 0)');
    }

    public function testJoinConditionsAreSupportedForManyToManyInnerJoins()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery('SELECT u.name, g.id FROM User u INNER JOIN u.Group g ON g.id > 2');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e2.id AS e2__id FROM entity e INNER JOIN group_user g ON (e.id = g.user_id) INNER JOIN entity e2 ON (e2.id > 2) AND e2.type = 1 WHERE (e.type = 0)');
    }

    public function testJoinConditionsWithClauseAndAliases()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery('SELECT a.name, b.id FROM User a LEFT JOIN a.Phonenumber b ON a.name = b.phonenumber');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON (e.name = p.phonenumber) WHERE (e.type = 0)');
    }

    public function testJoinWithConditionAndNotInClause()
    {
        // Related to ticket #1329
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery('SELECT a.name, b.id FROM User a LEFT JOIN a.Phonenumber b WITH a.id NOT IN (1, 2, 3)');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id AND (e.id NOT IN (1, 2, 3)) WHERE (e.type = 0)');
    }
}
