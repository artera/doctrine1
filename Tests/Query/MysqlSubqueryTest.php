<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class MysqlSubqueryTest extends DoctrineUnitTestCase
{
    public function setUp(): void
    {
        static::$dbh  = new \Doctrine_Adapter_Mock('mysql');
        static::$conn = \Doctrine_Manager::getInstance()->openConnection(static::$dbh);
    }

    public function testGetLimitSubquerSupportsOrderByWithAggregateValues()
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        static::$dbh->pop();

        $this->assertEquals(static::$dbh->pop(), 'SELECT DISTINCT e2.id, COUNT(DISTINCT a2.id) AS a2__0 FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id ORDER BY a2__0 LIMIT 5');
    }

    public function testGetLimitSubquerySupportsOrderByWithAggregateValuesAndDescKeyword()
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums DESC, u.name');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        static::$dbh->pop();

        $this->assertEquals(static::$dbh->pop(), 'SELECT DISTINCT e2.id, e2.name, COUNT(DISTINCT a2.id) AS a2__0 FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id ORDER BY a2__0 DESC, e2.name LIMIT 5');
    }

    public function testGetLimitSubquerySupportsOrderByWithAggregateValuesAndColumns()
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums, u.name');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        static::$dbh->pop();

        $this->assertEquals(static::$dbh->pop(), 'SELECT DISTINCT e2.id, e2.name, COUNT(DISTINCT a2.id) AS a2__0 FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id ORDER BY a2__0, e2.name LIMIT 5');
    }

    public function testGetLimitSubquerySupportsOrderByAndHavingWithAggregateValues()
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums DESC');
        $q->having('num_albums > 0');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        static::$dbh->pop();

        $this->assertEquals(static::$dbh->pop(), 'SELECT DISTINCT e2.id, COUNT(DISTINCT a2.id) AS a2__0 FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id HAVING a2__0 > 0 ORDER BY a2__0 DESC LIMIT 5');
    }

    public function testGetLimitSubquerySupportsHavingWithAggregateValues()
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->having('num_albums > 0');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        static::$dbh->pop();

        $this->assertEquals(static::$dbh->pop(), 'SELECT DISTINCT e2.id, COUNT(DISTINCT a2.id) AS a2__0 FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id HAVING a2__0 > 0 LIMIT 5');
    }
}
