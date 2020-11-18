<?php
namespace Tests\Sequence;

use Tests\DoctrineUnitTestCase;

class PgsqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Pgsql';

    public function testCurrIdExecutesSql()
    {
        static::$conn->sequence->currId('user');
        $q = 'SELECT last_value FROM user_seq';

        $this->assertEquals(static::$adapter->pop(), $q);
    }

    public function testNextIdExecutesSql()
    {
        $id = static::$conn->sequence->nextId('user');

        $this->assertEquals(static::$adapter->pop(), "SELECT NEXTVAL('user_seq')");
    }

    public function testLastInsertIdExecutesSql()
    {
        static::$conn->sequence->lastInsertId('user');

        $this->assertEquals(static::$adapter->pop(), "SELECT CURRVAL('user_seq')");
    }
}
