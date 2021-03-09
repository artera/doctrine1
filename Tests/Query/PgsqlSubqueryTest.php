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

    public function testGetLimitSubqueryWithOrderByOnAggregateValues(): void
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        $this->assertMatchesSnapshot(static::$dbh->pop());
    }

    public function testGetLimitSubqueryWithOrderByOnAggregateValuesAndColumns(): void
    {
        $q = new \Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums, u.name');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        $this->assertMatchesSnapshot(static::$dbh->pop());
    }
}
