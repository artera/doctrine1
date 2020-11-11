<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class FromTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testCount()
    {
        $count = \Doctrine_Query::create()->from('User')->count();

        $this->assertEquals($count, 0);
    }
    public function testLeftJoin()
    {
        $q = new \Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 WHERE (e.type = 0)');
    }

    public function testDefaultJoinIsLeftJoin()
    {
        $q = new \Doctrine_Query();

        $q->from('User u JOIN u.Group');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 WHERE (e.type = 0)');
    }

    public function testInnerJoin()
    {
        $q = new \Doctrine_Query();

        $q->from('User u INNER JOIN u.Group');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id FROM entity e INNER JOIN group_user g ON (e.id = g.user_id) INNER JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 WHERE (e.type = 0)');
    }

    public function testMultipleLeftJoin()
    {
        $q = new \Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group LEFT JOIN u.Phonenumber');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testMultipleLeftJoin2()
    {
        $q = new \Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group LEFT JOIN u.Phonenumber');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testMultipleInnerJoin()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name')->from('User u INNER JOIN u.Group INNER JOIN u.Phonenumber');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e INNER JOIN group_user g ON (e.id = g.user_id) INNER JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 INNER JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testMultipleInnerJoin2()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name')->from('User u INNER JOIN u.Group, u.Phonenumber');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e INNER JOIN group_user g ON (e.id = g.user_id) INNER JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 INNER JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testMixingOfJoins()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name, g.name, p.phonenumber')->from('User u INNER JOIN u.Group g LEFT JOIN u.Phonenumber p');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e2.id AS e2__id, e2.name AS e2__name, p.id AS p__id, p.phonenumber AS p__phonenumber FROM entity e INNER JOIN group_user g ON (e.id = g.user_id) INNER JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testMixingOfJoins2()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name, g.name, p.phonenumber')->from('User u INNER JOIN u.Group g LEFT JOIN g.Phonenumber p');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e2.id AS e2__id, e2.name AS e2__name, p.id AS p__id, p.phonenumber AS p__phonenumber FROM entity e INNER JOIN group_user g ON (e.id = g.user_id) INNER JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 LEFT JOIN phonenumber p ON e2.id = p.entity_id WHERE (e.type = 0)');
    }
}
