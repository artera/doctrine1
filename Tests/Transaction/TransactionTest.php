<?php
namespace Tests\Transaction;

use Tests\DoctrineUnitTestCase;

class TransactionTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Sqlite';
    protected static $transaction;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$transaction = new \Doctrine1\Transaction\Mock();
    }

    public function testGetIsolationIsOnlyImplementedAtDriverLevel()
    {
        $this->expectException(\Doctrine1\Transaction\Exception::class);
        static::$transaction->GetIsolation('READ UNCOMMITTED');
    }

    public function testTransactionLevelIsInitiallyZero()
    {
        $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
    }

    public function testSubsequentTransactionsAfterRollback()
    {
        $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
        static::$transaction->beginTransaction();
        $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::ACTIVE);

        static::$transaction->rollback();
        $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
        static::$transaction->beginTransaction();
        $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::ACTIVE);
        static::$transaction->commit();
        $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);

        $i = 0;
        while ($i < 5) {
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
            static::$transaction->beginTransaction();
            $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::ACTIVE);
            try {
                if ($i == 0) {
                    throw new \Exception();
                }
                static::$transaction->commit();
            } catch (\Exception $e) {
                static::$transaction->rollback();
                $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
            }
            ++$i;
        }
    }

    public function testGetStateReturnsStateConstant()
    {
        $this->assertEquals(static::$transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
    }

    public function testCommittingWithNoActiveTransactionThrowsException()
    {
        $this->expectException(\Doctrine1\Transaction\Exception::class);
        static::$transaction->commit();
    }

    public function testExceptionIsThrownWhenUsingRollbackOnNotActiveTransaction()
    {
        $this->expectException(\Doctrine1\Transaction\Exception::class);
        static::$transaction->rollback();
    }

    public function testBeginTransactionStartsNewTransaction()
    {
        static::$transaction->beginTransaction();

        $this->assertEquals(static::$adapter->pop(), 'BEGIN TRANSACTION');
    }

    public function testCommitMethodCommitsCurrentTransaction()
    {
        static::$transaction->commit();

        $this->assertEquals(static::$adapter->pop(), 'COMMIT');
    }
    public function testNestedTransaction()
    {
        $conn = \Doctrine1\Manager::connection();

        try {
            $conn->beginTransaction();

            // Create new \client
            $user = new \User();
            $user->set('name', 'Test User');
            $user->save();

            // Create new \credit card
            $phonenumber = new \Phonenumber();
            $phonenumber->set('entity_id', $user->get('id'));
            $phonenumber->set('phonenumber', '123 123');
            $phonenumber->save();

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
        }

        $this->assertTrue($user->id > 0);
        $this->assertTrue($phonenumber->id > 0);
    }
}
