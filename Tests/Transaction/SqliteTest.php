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
        static::$transaction = new \Doctrine_Transaction_Sqlite();
    }

    public function testSetIsolationThrowsExceptionOnUnknownIsolationMode()
    {
        $this->expectException(\Doctrine_Transaction_Exception::class);
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
