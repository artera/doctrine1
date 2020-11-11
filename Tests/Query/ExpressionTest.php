<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class ExpressionTest extends DoctrineUnitTestCase
{
    public function testUnknownExpressionInSelectClauseThrowsException()
    {
        // Activate portability all
        static::$conn->setAttribute(\Doctrine_Core::ATTR_PORTABILITY, \Doctrine_Core::PORTABILITY_ALL);

        $this->expectException(\Doctrine_Query_Exception::class);
        $q = \Doctrine_Query::create()
            ->parseDqlQuery("SELECT SOMEUNKNOWNFUNC(u.name, ' ', u.loginname) FROM User u");
        $sql = $q->getSqlQuery();

        // Reassign old portability mode
        static::$conn->setAttribute(\Doctrine_Core::ATTR_PORTABILITY, \Doctrine_Core::PORTABILITY_ALL);
    }


    public function testUnknownExpressionInSelectClauseDoesntThrowException()
    {
        // Deactivate portability expression mode
        static::$conn->setAttribute(\Doctrine_Core::ATTR_PORTABILITY, \Doctrine_Core::PORTABILITY_ALL ^ \Doctrine_Core::PORTABILITY_EXPR);

        $q = \Doctrine_Query::create()
            ->parseDqlQuery("SELECT SOMEUNKNOWNFUNC(u.name, ' ', u.loginname) FROM User u");
        $sql = $q->getSqlQuery();

// Reassign old portability mode
        static::$conn->setAttribute(\Doctrine_Core::ATTR_PORTABILITY, \Doctrine_Core::PORTABILITY_ALL);
    }

    public function testUnknownColumnWithinFunctionInSelectClauseThrowsException()
    {
        $q = new \Doctrine_Query();

        $this->expectException(\Doctrine_Query_Exception::class);
        $q->parseDqlQuery('SELECT CONCAT(u.name, u.unknown) FROM User u');
        $q->execute();
    }

    public function testConcatIsSupportedInSelectClause()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.id, CONCAT(u.name, u.loginname) FROM User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, CONCAT(e.name, e.loginname) AS e__0 FROM entity e WHERE (e.type = 0)');
    }

    public function testConcatInSelectClauseSupportsLiteralStrings()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery("SELECT u.id, CONCAT(u.name, 'The Man') FROM User u");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, CONCAT(e.name, 'The Man') AS e__0 FROM entity e WHERE (e.type = 0)");
    }

    public function testConcatInSelectClauseSupportsMoreThanTwoArgs()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery("SELECT u.id, CONCAT(u.name, 'The Man', u.loginname) FROM User u");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id, CONCAT(e.name, 'The Man', e.loginname) AS e__0 FROM entity e WHERE (e.type = 0)");
    }

    public function testNonPortableFunctionsAreSupported()
    {
        $query = new \Doctrine_Query();
        // we are using stored procedure here, so adjust portability settings
        static::$conn->setAttribute(\Doctrine_Core::ATTR_PORTABILITY, \Doctrine_Core::PORTABILITY_ALL ^ \Doctrine_Core::PORTABILITY_EXPR);

        $lat    = '13.23';
        $lon    = '33.23';
        $radius = '33';

        $query->select("l.*, GeoDistKM(l.lat, l.lon, $lat, $lon) distance")
            ->from('Location l')
            ->where('l.id <> ? AND l.lat > ?', [1, 0])
            ->having("distance < $radius")
            ->orderby('distance ASC')
            ->groupby('l.id')
            ->limit(5);

        $this->assertEquals($query->getSqlQuery(), 'SELECT l.id AS l__id, l.lat AS l__lat, l.lon AS l__lon, GeoDistKM(l.lat, l.lon, 13.23, 33.23) AS l__0 FROM location l WHERE (l.id <> ? AND l.lat > ?) GROUP BY l.id HAVING l__0 < 33 ORDER BY l__0 ASC LIMIT 5');

        static::$conn->setAttribute(\Doctrine_Core::ATTR_PORTABILITY, \Doctrine_Core::PORTABILITY_ALL);
    }
}
