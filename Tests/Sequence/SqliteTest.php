<?php
namespace Tests\Sequence;

use Tests\DoctrineUnitTestCase;

class SqliteTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Sqlite';

    public function testCurrIdExecutesSql()
    {
        static::$adapter->forceLastInsertIdFail(false);

        static::$conn->sequence->currId('user');

        $this->assertEquals(static::$adapter->pop(), 'SELECT MAX(id) FROM user_seq');
    }

    public function testNextIdExecutesSql()
    {
        $id = static::$conn->sequence->nextId('user');

        $this->assertEquals($id, 1);

        $this->assertEquals(static::$adapter->pop(), 'DELETE FROM user_seq WHERE id < 1');
        $this->assertEquals(static::$adapter->pop(), 'LAST_INSERT_ID()');
        $this->assertEquals(static::$adapter->pop(), 'INSERT INTO user_seq (id) VALUES (NULL)');
    }

    public function testLastInsertIdCallsPdoLevelEquivalent()
    {
        $id = static::$conn->sequence->lastInsertId('user');

        $this->assertEquals($id, 1);

        $this->assertEquals(static::$adapter->pop(), 'LAST_INSERT_ID()');
    }
}
