<?php
namespace Tests\Transaction;

use Tests\DoctrineUnitTestCase;

class PgsqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Pgsql';
    protected static $transaction;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$transaction = new \Doctrine1\Transaction\Pgsql();
    }

    public function testCreateSavePointExecutesSql()
    {
        static::$transaction->beginTransaction('mypoint');

        $this->assertEquals(static::$adapter->pop(), 'SAVEPOINT mypoint');
    }

    public function testReleaseSavePointExecutesSql()
    {
        static::$transaction->commit('mypoint');

        $this->assertEquals(static::$adapter->pop(), 'RELEASE SAVEPOINT mypoint');
    }

    public function testRollbackSavePointExecutesSql()
    {
        static::$transaction->beginTransaction('mypoint');
        static::$transaction->rollback('mypoint');

        $this->assertEquals(static::$adapter->pop(), 'ROLLBACK TO SAVEPOINT mypoint');
    }

    public function testSetIsolationThrowsExceptionOnUnknownIsolationMode()
    {
        $this->expectException(\Doctrine1\Transaction\Exception::class);
        static::$transaction->setIsolation('unknown');
    }

    public function testSetIsolationExecutesSql()
    {
        static::$transaction->setIsolation('READ UNCOMMITTED');

        $this->assertEquals(static::$adapter->pop(), 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
    }
}
