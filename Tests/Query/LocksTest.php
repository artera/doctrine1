<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class LocksTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public static function prepareTables(): void
    {
    }

    public function testForUpdate()
    {
        $query = new \Doctrine1\Query(static::$connection);

        $query->select('User.id')->from('User')->where("User.name LIKE 'z%' OR User.name LIKE 's%'")->forUpdate();
        $sql = "SELECT e.id AS e__id FROM entity e WHERE (e.name LIKE 'z%' OR e.name LIKE 's%') AND (e.type = 0)";
        $this->assertEquals($query->getSqlQuery(), "$sql FOR UPDATE");

        $query->noWait();
        $this->assertEquals($query->getSqlQuery(), "$sql FOR UPDATE NOWAIT");

        $query->skipLocked();
        $this->assertEquals($query->getSqlQuery(), "$sql FOR UPDATE SKIP LOCKED");
    }

    public function testForShare()
    {
        $query = new \Doctrine1\Query(static::$connection);

        $query->select('User.id')->from('User')->where("User.name LIKE 'z%' OR User.name LIKE 's%'")->forShare();
        $sql = "SELECT e.id AS e__id FROM entity e WHERE (e.name LIKE 'z%' OR e.name LIKE 's%') AND (e.type = 0)";
        $this->assertEquals($query->getSqlQuery(), "$sql FOR SHARE");

        $query->noWait();
        $this->assertEquals($query->getSqlQuery(), "$sql FOR SHARE NOWAIT");

        $query->skipLocked();
        $this->assertEquals($query->getSqlQuery(), "$sql FOR SHARE SKIP LOCKED");
    }
}
