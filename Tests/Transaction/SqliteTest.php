<?php
namespace Tests\Transaction;

use Tests\DoctrineUnitTestCase;

class SqliteTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Sqlite';
    protected static $transaction;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$transaction = new \Doctrine1\Transaction\Sqlite();
    }

    public function testSetIsolationThrowsExceptionOnUnknownIsolationMode()
    {
        $this->expectException(\Doctrine1\Transaction\Exception::class);
        static::$transaction->setIsolation('unknown');
    }

    public function testSetIsolationExecutesSql()
    {
        static::$transaction->setIsolation('READ UNCOMMITTED');
        static::$transaction->setIsolation('READ COMMITTED');
        static::$transaction->setIsolation('REPEATABLE READ');
        static::$transaction->setIsolation('SERIALIZABLE');

        $this->assertEquals(static::$adapter->pop(), 'PRAGMA read_uncommitted = 1');
        $this->assertEquals(static::$adapter->pop(), 'PRAGMA read_uncommitted = 1');
        $this->assertEquals(static::$adapter->pop(), 'PRAGMA read_uncommitted = 1');
        $this->assertEquals(static::$adapter->pop(), 'PRAGMA read_uncommitted = 0');
    }
}
