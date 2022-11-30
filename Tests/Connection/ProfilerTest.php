<?php
namespace Tests\Connection;

use Tests\DoctrineUnitTestCase;

class ProfilerTest extends DoctrineUnitTestCase
{
    protected \Doctrine1\Connection\Profiler $profiler;

    public static function prepareTables(): void
    {
    }
    public static function prepareData(): void
    {
    }

    public function setUp(): void
    {
        $this->profiler = new \Doctrine1\Connection\Profiler();
        static::$conn->setListener($this->profiler);
    }

    public function testQuery()
    {
        static::$conn->exec('CREATE TABLE test (id INT)');

        $this->assertEquals($this->profiler->lastEvent()->getQuery(), 'CREATE TABLE test (id INT)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::CONN_EXEC);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $this->assertEquals(static::$conn->count(), 1);
    }

    public function testPrepareAndExecute()
    {
        $stmt  = static::$conn->prepare('INSERT INTO test (id) VALUES (?)');
        $event = $this->profiler->lastEvent();

        $this->assertEquals($event->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::CONN_PREPARE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $stmt->execute([1]);

        $this->assertEquals($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::STMT_EXECUTE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $this->assertEquals(static::$conn->count(), 2);
    }

    public function testMultiplePrepareAndExecute()
    {
        $stmt = static::$conn->prepare('INSERT INTO test (id) VALUES (?)');
        $this->assertEquals($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::CONN_PREPARE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $stmt2 = static::$conn->prepare('INSERT INTO test (id) VALUES (?)');
        $this->assertEquals($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::CONN_PREPARE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }

    public function testExecuteStatementMultipleTimes()
    {
        $stmt = static::$conn->prepare('INSERT INTO test (id) VALUES (?)');
        $stmt->execute([1]);
        $stmt->execute([1]);

        $this->assertEquals($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::STMT_EXECUTE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        $this->assertEquals($this->profiler->lastEvent()->getQuery(), 'INSERT INTO test (id) VALUES (?)');
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::STMT_EXECUTE);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }

    public function testTransactionRollback()
    {
        static::$conn->beginTransaction();

        $this->assertEquals($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::TX_BEGIN);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        static::$conn->rollback();

        $this->assertEquals($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::TX_ROLLBACK);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }

    public function testTransactionCommit()
    {
        static::$conn->beginTransaction();

        $this->assertEquals($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::TX_BEGIN);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));

        static::$conn->commit();

        $this->assertEquals($this->profiler->lastEvent()->getQuery(), null);
        $this->assertTrue($this->profiler->lastEvent()->hasEnded());
        $this->assertEquals($this->profiler->lastEvent()->getCode(), \Doctrine1\Event::TX_COMMIT);
        $this->assertTrue(is_numeric($this->profiler->lastEvent()->getElapsedSecs()));
    }
}
