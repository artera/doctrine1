<?php
namespace Tests\Sequence;

use Tests\DoctrineUnitTestCase;

class MysqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Mysql';

    public function testCurrIdExecutesSql()
    {
        static::$conn->sequence->currId('user');

        $this->assertEquals(static::$adapter->pop(), 'SELECT MAX(id) FROM user');
    }

    public function testNextIdExecutesSql()
    {
        $id = static::$conn->sequence->nextId('user');

        $this->assertEquals($id, 1);

        $this->assertEquals(static::$adapter->pop(), 'DELETE FROM user WHERE id < 1');
        $this->assertEquals(static::$adapter->pop(), 'LAST_INSERT_ID()');
        $this->assertEquals(static::$adapter->pop(), 'INSERT INTO user (id) VALUES (NULL)');
    }

    public function testLastInsertIdCallsPdoLevelEquivalent()
    {
        $id = static::$conn->sequence->lastInsertId('user');

        $this->assertEquals($id, 1);

        $this->assertEquals(static::$adapter->pop(), 'LAST_INSERT_ID()');
    }
}
