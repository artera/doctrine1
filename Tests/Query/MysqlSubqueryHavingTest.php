<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class MysqlSubqueryHavingTest extends DoctrineUnitTestCase
{
    public function setUp(): void
    {
        static::$dbh  = new \Doctrine1\Adapter\Mock('mysql');
        static::$conn = \Doctrine1\Manager::getInstance()->openConnection(static::$dbh);
    }

    public function testGetLimitSubqueryWithHavingOnAggregateValues(): void
    {
        $q = new \Doctrine1\Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums DESC');
        $q->having('num_albums > 0');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        $this->assertMatchesSnapshot(static::$dbh->pop());
    }

    public function testGetLimitSubqueryWithHavingOnAggregateValuesIncorrectAlias(): void
    {
        $q = new \Doctrine1\Query();
        $q->select('u.name, COUNT(a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums DESC');
        $q->having('num_albums > 0');
        $q->groupby('u.id');
        $q->limit(5);

        $q->execute();

        $this->assertMatchesSnapshot(static::$dbh->pop());
    }
}
