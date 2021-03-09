<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class SubqueryTest extends DoctrineUnitTestCase
{
    public function testSubqueryWithWherePartAndInExpression(): void
    {
        $q = new \Doctrine_Query();
        $q->from('User u')->where("u.id NOT IN (SELECT u2.id FROM User u2 WHERE u2.name = 'zYne')");

        $this->assertMatchesSnapshot($q->getSqlQuery());

        $users = $q->execute();

        $this->assertCount(7, $users);
        $this->assertEquals($users[0]->name, 'Arnold Schwarzenegger');
    }

    public function testSubqueryAllowsSelectingOfAnyField(): void
    {
        $q = new \Doctrine_Query();
        $q->from('User u')->where('u.id NOT IN (SELECT g.user_id FROM GroupUser g)');

        $this->assertMatchesSnapshot($q->getSqlQuery());
    }

    public function testSubqueryInSelectPart(): void
    {
        // ticket #307
        $q = new \Doctrine_Query();

        $q->parseDqlQuery("SELECT u.name, (SELECT COUNT(p.id) FROM Phonenumber p WHERE p.entity_id = u.id) pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");

        $this->assertMatchesSnapshot($q->getSqlQuery());
        // test consequent call
        $this->assertMatchesSnapshot($q->getSqlQuery());

        $users = $q->execute();

        $this->assertCount(1, $users);

        $this->assertEquals($users[0]->name, 'zYne');
        $this->assertEquals($users[0]->pcount, 1);
    }

    public function testSubqueryInSelectPartWithRawSql(): void
    {
        // ticket DC-706
        $q = new \Doctrine_Query();

        $q->parseDqlQuery("SELECT u.name, (SQL:SELECT COUNT(p.id) AS p__0 FROM phonenumber p WHERE (p.entity_id = e.id)) as pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");

        $this->assertMatchesSnapshot($q->getSqlQuery());
        // test consequent call
        $this->assertMatchesSnapshot($q->getSqlQuery());

        $users = $q->execute();

        $this->assertCount(1, $users);

        $this->assertEquals($users[0]->name, 'zYne');
        $this->assertEquals($users[0]->pcount, 1);
    }

    public function testSubqueryInSelectPart2(): void
    {
        // ticket #307
        $q = new \Doctrine_Query();

        $q->parseDqlQuery("SELECT u.name, (SELECT COUNT(w.id) FROM User w WHERE w.id = u.id) pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");
        $this->assertMatchesSnapshot($q->getSqlQuery());
    }

    public function testGetLimitSubqueryOrderBy2(): void
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums')
            ->from('User u, u.Album a')
            ->orderby('num_albums')
            ->groupby('u.id');

        // this causes getLimitSubquery() to be used, and it fails
        $q->limit(5);

        $users = $q->execute();

        $count = $users->count();
        $this->assertMatchesSnapshot($q->getSqlQuery());
    }

    public function testAggregateFunctionsInOrderByAndHavingWithCount(): void
    {
        $q = new \Doctrine_Query();

        $q->select('u.*, COUNT(a.id) num_albums')
            ->from('User u')
            ->leftJoin('u.Album a')
            ->orderby('num_albums desc')
            ->groupby('u.id')
            ->having('num_albums > 0')
            ->limit(5);

        $this->assertMatchesSnapshot($q->getSqlQuery());
        $q->count();
    }
}
