<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class ConditionTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    public static function prepareTables(): void
    {
    }

    /** @todo belongs in TokenizerTestCase? */
    public function testBracktExplode()
    {
        $tokenizer = new \Doctrine1\Query\Tokenizer();
        $str       = 'item OR item OR item';
        $parts     = $tokenizer->bracketExplode($str, [' OR '], '(', ')');

        $this->assertEquals($parts, ['item','item','item']);
    }

    public function testConditionParser()
    {
        $query = new \Doctrine1\Query(static::$connection);

        $query->select('User.id')->from('User')->where("User.name LIKE 'z%' OR User.name LIKE 's%'");

        $sql = "SELECT e.id AS e__id FROM entity e WHERE (e.name LIKE 'z%' OR e.name LIKE 's%') AND (e.type = 0)";
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(User.name LIKE 'z%') OR (User.name LIKE 's%')");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("((User.name LIKE 'z%') OR (User.name LIKE 's%'))");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR (User.name LIKE 's%')))");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR User.name LIKE 's%'))");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(User.name LIKE 'z%') OR User.name LIKE 's%' AND User.name LIKE 'a%'");

        $this->assertEquals($query->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE (e.name LIKE 'z%' OR (e.name LIKE 's%' AND e.name LIKE 'a%')) AND (e.type = 0)");

        $query->where("(((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%'");
        $this->assertEquals($query->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE ((e.name LIKE 'z%' OR e.name LIKE 's%') AND e.name LIKE 'a%') AND (e.type = 0)");

        $query->where("((((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%')");
        $this->assertEquals($query->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE ((e.name LIKE 'z%' OR e.name LIKE 's%') AND e.name LIKE 'a%') AND (e.type = 0)");

        $query->where("(((((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%'))");
        $this->assertEquals($query->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE ((e.name LIKE 'z%' OR e.name LIKE 's%') AND e.name LIKE 'a%') AND (e.type = 0)");
    }

    public function testConditionParser2()
    {
        $query = new \Doctrine1\Query(static::$connection);

        $query->select('User.id')->from('User')->where("User.name LIKE 'z%' OR User.name LIKE 's%'");

        $sql = "SELECT e.id AS e__id FROM entity e WHERE (e.name LIKE 'z%' OR e.name LIKE 's%') AND (e.type = 0)";
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(User.name LIKE 'z%') OR (User.name LIKE 's%')");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("((User.name LIKE 'z%') OR (User.name LIKE 's%'))");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR (User.name LIKE 's%')))");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(((User.name LIKE 'z%') OR User.name LIKE 's%'))");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(User.name LIKE 'z%') OR User.name LIKE 's%' AND User.name LIKE 'a%'");

        $sql = "SELECT e.id AS e__id FROM entity e WHERE ((e.name LIKE 'z%' OR e.name LIKE 's%') AND e.name LIKE 'a%') AND (e.type = 0)";

        $this->assertEquals($query->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE (e.name LIKE 'z%' OR (e.name LIKE 's%' AND e.name LIKE 'a%')) AND (e.type = 0)");

        $query->where("(((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%'");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("((((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%')");
        $this->assertEquals($query->getSqlQuery(), $sql);

        $query->where("(((((User.name LIKE 'z%') OR User.name LIKE 's%')) AND User.name LIKE 'a%'))");
        $this->assertEquals($query->getSqlQuery(), $sql);
    }
}
