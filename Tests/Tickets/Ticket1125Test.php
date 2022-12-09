<?php

namespace Tests\Tickets;

use Doctrine1\Column;
use Doctrine1\Column\Type;
use Tests\DoctrineUnitTestCase;

class Ticket1125Test extends DoctrineUnitTestCase
{
    public function setUp(): void
    {
        static::$dbh  = new \Doctrine1\Adapter\Mock('mysql');
        static::$conn = \Doctrine1\Manager::getInstance()->openConnection(static::$dbh);
    }

    public function testTest()
    {
        $fields = [
            new Column('id', Type::Integer, 4, primary: true, autoincrement: true),
            new Column('name', Type::String),
        ];
        static::$conn->export->createTable('test', $fields);
        $this->assertEquals(static::$dbh->pop(), 'CREATE TABLE test (id INT AUTO_INCREMENT, name TEXT, PRIMARY KEY(id)) ENGINE = InnoDB');
    }
}
