<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class DriverTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    public static function prepareTables(): void
    {
    }

    public function testLimitQueriesForPgsql()
    {
        static::$dbh = new \Doctrine1\Adapter\Mock('pgsql');

        $conn = static::$manager->openConnection(static::$dbh);

        $q = new \Doctrine1\Query($conn);

        $q->from('User u')->limit(5);

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0) LIMIT 5');
    }

    public function testLimitQueriesForSqlite()
    {
        static::$dbh = new \Doctrine1\Adapter\Mock('sqlite');

        $conn = static::$manager->openConnection(static::$dbh);

        $q = new \Doctrine1\Query($conn);

        $q->from('User u')->limit(5);

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0) LIMIT 5');
    }

    public function testLimitQueriesForMysql()
    {
        static::$dbh = new \Doctrine1\Adapter\Mock('mysql');

        $conn = static::$manager->openConnection(static::$dbh);

        $q = new \Doctrine1\Query($conn);

        $q->from('User u')->limit(5);

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0) LIMIT 5');
    }
}
