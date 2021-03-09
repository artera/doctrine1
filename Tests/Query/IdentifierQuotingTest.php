<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class IdentifierQuotingTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        static::$tables = ['Entity', 'Phonenumber'];

        parent::prepareTables();
    }

    public static function prepareData(): void
    {
    }

    public function testQuerySupportsIdentifierQuoting()
    {
        static::$conn->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true);

        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.id, MAX(u.id), MIN(u.name) FROM User u');

        $this->assertEquals('SELECT "e"."id" AS "e__id", MAX("e"."id") AS "e__0", MIN("e"."name") AS "e__1" FROM "entity" "e" WHERE ("e"."type" = 0)', $q->getSqlQuery());

        $q->execute();
    }

    public function testQuerySupportsIdentifierQuotingInWherePart()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.name FROM User u WHERE u.id = 3');

        $this->assertEquals('SELECT "e"."id" AS "e__id", "e"."name" AS "e__name" FROM "entity" "e" WHERE ("e"."id" = 3 AND ("e"."type" = 0))', $q->getSqlQuery());

        $q->execute();
    }

    public function testQuerySupportsIdentifierQuotingWithJoins()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $this->assertEquals('SELECT "e"."id" AS "e__id", "e"."name" AS "e__name" FROM "entity" "e" LEFT JOIN "phonenumber" "p" ON "e"."id" = "p"."entity_id" WHERE ("e"."type" = 0)', $q->getSqlQuery());
    }

    public function testLimitSubqueryAlgorithmSupportsIdentifierQuoting()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.name FROM User u INNER JOIN u.Phonenumber p')->limit(5);

        $this->assertEquals('SELECT "e"."id" AS "e__id", "e"."name" AS "e__name" FROM "entity" "e" INNER JOIN "phonenumber" "p" ON "e"."id" = "p"."entity_id" WHERE "e"."id" IN (SELECT DISTINCT "e2"."id" FROM "entity" "e2" INNER JOIN "phonenumber" "p2" ON "e2"."id" = "p2"."entity_id" WHERE ("e2"."type" = 0) LIMIT 5)', $q->getSqlQuery());
    }

    public function testCountQuerySupportsIdentifierQuoting()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.name FROM User u INNER JOIN u.Phonenumber p');

        $this->assertEquals('SELECT COUNT(*) AS "num_results" FROM (SELECT "e"."id" FROM "entity" "e" INNER JOIN "phonenumber" "p" ON "e"."id" = "p"."entity_id" WHERE ("e"."type" = 0) GROUP BY "e"."id") "dctrn_count_query"', $q->getCountSqlQuery());
    }

    public function testUpdateQuerySupportsIdentifierQuoting()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('UPDATE User u SET u.name = ? WHERE u.id = ?');

        $this->assertEquals('UPDATE "entity" SET "name" = ? WHERE ("id" = ? AND ("type" = 0))', $q->getSqlQuery());
    }

    public function testUpdateQuerySupportsIdentifierQuoting2()
    {
        $q = new \Doctrine_Query();

        $q->update('User')->set('name', '?', 'guilhermeblanco')->where('id = ?');

        $this->assertEquals('UPDATE "entity" SET "name" = ? WHERE ("id" = ? AND ("type" = 0))', $q->getSqlQuery());
    }

    public function testUpdateQuerySupportsIdentifierQuoting3()
    {
        $q = new \Doctrine_Query();

        $q->update('User')->set('name', 'LOWERCASE(name)')->where('id = ?');

        $this->assertEquals('UPDATE "entity" SET "name" = LOWERCASE("name") WHERE ("id" = ? AND ("type" = 0))', $q->getSqlQuery());
    }

    public function testUpdateQuerySupportsIdentifierQuoting4()
    {
        $q = new \Doctrine_Query();

        $q->update('User u')->set('u.name', 'LOWERCASE(u.name)')->where('u.id = ?');

        $this->assertEquals('UPDATE "entity" SET "name" = LOWERCASE("name") WHERE ("id" = ? AND ("type" = 0))', $q->getSqlQuery());
    }

    public function testUpdateQuerySupportsIdentifierQuoting5()
    {
        $q = new \Doctrine_Query();

        $q->update('User u')->set('u.name', 'UPPERCASE(LOWERCASE(u.name))')->where('u.id = ?');

        $this->assertEquals('UPDATE "entity" SET "name" = UPPERCASE(LOWERCASE("name")) WHERE ("id" = ? AND ("type" = 0))', $q->getSqlQuery());
    }

    public function testUpdateQuerySupportsIdentifierQuoting6()
    {
        $q = new \Doctrine_Query();

        $q->update('User u')->set('u.name', 'UPPERCASE(LOWERCASE(u.id))')->where('u.id = ?');

        $this->assertEquals('UPDATE "entity" SET "name" = UPPERCASE(LOWERCASE("id")) WHERE ("id" = ? AND ("type" = 0))', $q->getSqlQuery());
    }

    public function testUpdateQuerySupportsIdentifierQuoting7()
    {
        $q = new \Doctrine_Query();

        $q->update('User u')->set('u.name', 'CURRENT_TIMESTAMP')->where('u.id = ?');

        $this->assertEquals('UPDATE "entity" SET "name" = CURRENT_TIMESTAMP WHERE ("id" = ? AND ("type" = 0))', $q->getSqlQuery());
    }

    public function testUpdateQuerySupportsIdentifierQuoting8()
    {
        $q = new \Doctrine_Query();

        $q->update('User u')->set('u.id', 'u.id + 1')->where('u.name = ?');

        $this->assertEquals('UPDATE "entity" SET "id" = "id" + 1 WHERE ("name" = ? AND ("type" = 0))', $q->getSqlQuery());

        static::$conn->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, false);
    }
}
