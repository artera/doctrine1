<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class SubqueryTest extends DoctrineUnitTestCase
{
    public function testSubqueryWithWherePartAndInExpression()
    {
        $q = new \Doctrine_Query();
        $q->from('User u')->where("u.id NOT IN (SELECT u2.id FROM User u2 WHERE u2.name = 'zYne')");

        $this->assertEquals(
            $q->getSqlQuery(),
            "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id NOT IN (SELECT e2.id AS e2__id FROM entity e2 WHERE (e2.name = 'zYne' AND (e2.type = 0))) AND (e.type = 0))"
        );

        $users = $q->execute();

        $this->assertEquals($users->count(), 7);
        $this->assertEquals($users[0]->name, 'Arnold Schwarzenegger');
    }

    public function testSubqueryAllowsSelectingOfAnyField()
    {
        $q = new \Doctrine_Query();
        $q->from('User u')->where('u.id NOT IN (SELECT g.user_id FROM GroupUser g)');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id NOT IN (SELECT g.user_id AS g__user_id FROM group_user g) AND (e.type = 0))');
    }

    public function testSubqueryInSelectPart()
    {
        // ticket #307
        $q = new \Doctrine_Query();

        $q->parseDqlQuery("SELECT u.name, (SELECT COUNT(p.id) FROM Phonenumber p WHERE p.entity_id = u.id) pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.name AS e__name, (SELECT COUNT(p.id) AS p__0 FROM phonenumber p WHERE (p.entity_id = e.id)) AS e__0 FROM entity e WHERE (e.name = 'zYne' AND (e.type = 0)) LIMIT 1");
        // test consequent call
        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.name AS e__name, (SELECT COUNT(p.id) AS p__0 FROM phonenumber p WHERE (p.entity_id = e.id)) AS e__0 FROM entity e WHERE (e.name = 'zYne' AND (e.type = 0)) LIMIT 1");

        $users = $q->execute();

        $this->assertEquals($users->count(), 1);

        $this->assertEquals($users[0]->name, 'zYne');
        $this->assertEquals($users[0]->pcount, 1);
    }

    public function testSubqueryInSelectPartWithRawSql()
    {
        // ticket DC-706
        $q = new \Doctrine_Query();

        $q->parseDqlQuery("SELECT u.name, (SQL:SELECT COUNT(p.id) AS p__0 FROM phonenumber p WHERE (p.entity_id = e.id)) as pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.name AS e__name, (SELECT COUNT(p.id) AS p__0 FROM phonenumber p WHERE (p.entity_id = e.id)) AS e__0 FROM entity e WHERE (e.name = 'zYne' AND (e.type = 0)) LIMIT 1");
        // test consequent call
        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.name AS e__name, (SELECT COUNT(p.id) AS p__0 FROM phonenumber p WHERE (p.entity_id = e.id)) AS e__0 FROM entity e WHERE (e.name = 'zYne' AND (e.type = 0)) LIMIT 1");

        $users = $q->execute();

        $this->assertEquals($users->count(), 1);

        $this->assertEquals($users[0]->name, 'zYne');
        $this->assertEquals($users[0]->pcount, 1);
    }

    public function testSubqueryInSelectPart2()
    {
        // ticket #307
        $q = new \Doctrine_Query();

        $q->parseDqlQuery("SELECT u.name, (SELECT COUNT(w.id) FROM User w WHERE w.id = u.id) pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");

        $this->assertNotEquals($q->getSqlQuery(), "SELECT e.id AS e__id, e.name AS e__name, (SELECT COUNT(e.id) AS e__0 FROM entity e WHERE e.id = e.id AND (e.type = 0)) AS e__0 FROM entity e WHERE e.name = 'zYne' AND (e.type = 0) LIMIT 1");
    }

    public function testGetLimitSubqueryOrderBy2()
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums');
        $q->groupby('u.id');

        // this causes getLimitSubquery() to be used, and it fails
            $q->limit(5);

            $users = $q->execute();

            $count = $users->count();

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, COUNT(DISTINCT a.id) AS a__0 FROM entity e LEFT JOIN album a ON e.id = a.user_id WHERE e.id IN (SELECT DISTINCT e2.id FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id ORDER BY COUNT(DISTINCT a2.id) LIMIT 5) AND (e.type = 0) GROUP BY e.id ORDER BY a__0');
    }

    public function testAggregateFunctionsInOrderByAndHavingWithCount()
    {
        $q = new \Doctrine_Query();

        $q->select('u.*, COUNT(a.id) num_albums')
            ->from('User u')
            ->leftJoin('u.Album a')
            ->orderby('num_albums desc')
            ->groupby('u.id')
            ->having('num_albums > 0')
            ->limit(5);

        $this->assertEquals($q->getCountSqlQuery(), 'SELECT COUNT(*) AS num_results FROM (SELECT e.id, COUNT(a.id) AS a__0 FROM entity e LEFT JOIN album a ON e.id = a.user_id WHERE (e.type = 0) GROUP BY e.id HAVING a__0 > 0) dctrn_count_query');
        $q->count();
    }
}
