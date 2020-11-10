<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1125Test extends DoctrineUnitTestCase
{
    public function setUp(): void
    {
        static::$dbh  = new \Doctrine_Adapter_Mock('mysql');
        static::$conn = \Doctrine_Manager::getInstance()->openConnection(static::$dbh);
    }

    public function testTest()
    {
        $fields = ['id' => ['primary'          => true,
                                         'autoincrement' => true,
                                         'type'          => 'integer',
                                         'length'        => 4],
                        'name' => ['type' => 'string']];
        static::$conn->export->createTable('test', $fields);
        $this->assertEquals(static::$dbh->pop(), 'CREATE TABLE test (id INT AUTO_INCREMENT, name TEXT, PRIMARY KEY(id)) ENGINE = INNODB');
    }
}
