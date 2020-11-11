<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class PgsqlSubqueryTest extends DoctrineUnitTestCase
{
    public function setUp(): void
    {
        static::$dbh  = new \Doctrine_Adapter_Mock('pgsql');
        static::$conn = \Doctrine_Manager::getInstance()->openConnection(static::$dbh);
    }

    public function testGetLimitSubqueryWithOrderByOnAggregateValues()
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        $this->assertEquals(
            static::$dbh->pop(),
            'SELECT e.id AS e__id, e.name AS e__name, COUNT(DISTINCT a.id) AS a__0 FROM entity e LEFT JOIN album a ON e.id = a.user_id WHERE e.id IN '
                                             . '(SELECT doctrine_subquery_alias.id FROM '
                                             . '(SELECT DISTINCT e2.id, COUNT(DISTINCT a2.id) AS a2__0 FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id ORDER BY a2__0 LIMIT 5) '
            . 'AS doctrine_subquery_alias) AND (e.type = 0) GROUP BY e.id ORDER BY a__0'
        );
    }

    public function testGetLimitSubqueryWithOrderByOnAggregateValuesAndColumns()
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums, u.name');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        $this->assertEquals(static::$dbh->pop(), 'SELECT e.id AS e__id, e.name AS e__name, COUNT(DISTINCT a.id) AS a__0 FROM entity e LEFT JOIN album a ON e.id = a.user_id WHERE e.id IN (SELECT doctrine_subquery_alias.id FROM (SELECT DISTINCT e2.id, e2.name, COUNT(DISTINCT a2.id) AS a2__0 FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id ORDER BY a2__0, e2.name LIMIT 5) AS doctrine_subquery_alias) AND (e.type = 0) GROUP BY e.id ORDER BY a__0, e.name');
    }
}
