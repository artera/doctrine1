<?php

namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class ComponentAliasTest extends DoctrineUnitTestCase
{
    public function testQueryWithSingleAlias()
    {
        static::$connection->clear();
        $q = new \Doctrine1\Query();

        $q->from('User u, u.Phonenumber');

        $users = $q->execute();

        $count = count(static::$conn);

        $this->assertEquals($users->count(), 8);
        $this->assertTrue($users[0]->Phonenumber instanceof \Doctrine1\Collection);
        $this->assertEquals(
            $q->getSqlQuery(),
            'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)'
        );
        $this->assertEquals($count, count(static::$conn));
    }

    public function testQueryWithNestedAliases()
    {
        static::$connection->clear();
        $q = new \Doctrine1\Query();

        $q->from('User u, u.Group g, g.Phonenumber');

        $users = $q->execute();

        $count = count(static::$conn);

        $this->assertEquals($users->count(), 8);
        $this->assertTrue($users[0]->Phonenumber instanceof \Doctrine1\Collection);
        $this->assertEquals(
            $q->getSqlQuery(),
            'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 LEFT JOIN phonenumber p ON e2.id = p.entity_id WHERE (e.type = 0)'
        );
        $this->assertEquals(($count + 1), count(static::$conn));
    }
    public function testQueryWithNestedAliasesAndArrayFetching()
    {
        static::$connection->clear();
        $q = new \Doctrine1\Query();

        $q->from('User u, u.Group g, g.Phonenumber');

        $users = $q->execute([], \Doctrine1\HydrationMode::Array);

        $count = count(static::$conn);

        $this->assertCount(8, $users);
        $this->assertCount(0, $users[7]['Group']);
        $this->assertCount(1, $users[1]['Group']);
    }

    public function testQueryWithMultipleNestedAliases()
    {
        static::$connection->clear();
        $q = new \Doctrine1\Query();

        $q->from('User u, u.Phonenumber, u.Group g, g.Phonenumber')->where('u.id IN (5,6)');

        $users = $q->execute();

        $count = count(static::$conn);


        $this->assertTrue($users[0]->Phonenumber instanceof \Doctrine1\Collection);
        $this->assertEquals(
            $q->getSqlQuery(),
            'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p2.id AS p2__id, p2.phonenumber AS p2__phonenumber, p2.entity_id AS p2__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 LEFT JOIN phonenumber p2 ON e2.id = p2.entity_id WHERE (e.id IN (5, 6) AND (e.type = 0))'
        );
        $this->assertCount(2, $users);
        $this->assertCount(1, $users[0]['Group']);
        $this->assertCount(1, $users[0]['Group'][0]['Phonenumber']);
        $this->assertCount(0, $users[1]['Group']);

        $this->assertEquals($count, count(static::$conn));
    }

    public function testQueryWithMultipleNestedAliasesAndArrayFetching()
    {
        $q = new \Doctrine1\Query();
        $q->from('User u, u.Phonenumber, u.Group g, g.Phonenumber')->where('u.id IN (5,6)');

        $users = $q->execute([], \Doctrine1\HydrationMode::Array);

        $this->assertCount(2, $users);
        $this->assertCount(1, $users[0]['Group']);
        $this->assertCount(1, $users[0]['Group'][0]['Phonenumber']);
        $this->assertCount(0, $users[1]['Group']);
    }
}
