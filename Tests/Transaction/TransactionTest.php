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
        static::$transaction = new \Doctrine_Transaction_Mock();
    }

    public function testCreateSavepointIsOnlyImplementedAtDriverLevel()
    {
        $this->expectException(\Doctrine_Transaction_Exception::class);
        static::$transaction->beginTransaction('savepoint');
    }

    public function testReleaseSavepointIsOnlyImplementedAtDriverLevel()
    {
        $this->expectException(\Doctrine_Transaction_Exception::class);
        static::$transaction->commit('savepoint');
    }

    public function testGetIsolationIsOnlyImplementedAtDriverLevel()
    {
        $this->expectException(\Doctrine_Transaction_Exception::class);
        static::$transaction->GetIsolation('READ UNCOMMITTED');
    }

    public function testTransactionLevelIsInitiallyZero()
    {
        $this->assertEquals(static::$transaction->getTransactionLevel(), 0);
    }

    public function testSubsequentTransactionsAfterRollback()
    {
        $this->assertEquals(0, static::$transaction->getTransactionLevel());
        $this->assertEquals(0, static::$transaction->getInternalTransactionLevel());
        static::$transaction->beginTransaction();
        $this->assertEquals(1, static::$transaction->getTransactionLevel());
        $this->assertEquals(0, static::$transaction->getInternalTransactionLevel());

        static::$transaction->rollback();
        $this->assertEquals(0, static::$transaction->getTransactionLevel());
        $this->assertEquals(0, static::$transaction->getInternalTransactionLevel());
        static::$transaction->beginTransaction();
        $this->assertEquals(1, static::$transaction->getTransactionLevel());
        $this->assertEquals(0, static::$transaction->getInternalTransactionLevel());
        static::$transaction->commit();
        $this->assertEquals(0, static::$transaction->getTransactionLevel());
        $this->assertEquals(0, static::$transaction->getInternalTransactionLevel());

        $i = 0;
        while ($i < 5) {
            $this->assertEquals(0, static::$transaction->getTransactionLevel());
            static::$transaction->beginTransaction();
            $this->assertEquals(1, static::$transaction->getTransactionLevel());
            try {
                if ($i == 0) {
                    throw new \Exception();
                }
                static::$transaction->commit();
            } catch (\Exception $e) {
                static::$transaction->rollback();
                $this->assertEquals(0, static::$transaction->getTransactionLevel());
            }
            ++$i;
        }
    }

    public function testGetStateReturnsStateConstant()
    {
        $this->assertEquals(static::$transaction->getState(), \Doctrine_Transaction::STATE_SLEEP);
    }

    public function testCommittingWithNoActiveTransactionThrowsException()
    {
        $this->expectException(\Doctrine_Transaction_Exception::class);
        static::$transaction->commit();
    }

    public function testExceptionIsThrownWhenUsingRollbackOnNotActiveTransaction()
    {
        $this->expectException(\Doctrine_Transaction_Exception::class);
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
        $conn = \Doctrine_Manager::connection();

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

    public function testAddDuplicateRecordToTransactionShouldSkipSecond()
    {
        $transaction = new \Doctrine_Transaction();
        $user        = new \User();
        $transaction->addInvalid($user);
        $this->assertEquals(1, count($transaction->getInvalid()));
        $transaction->addInvalid($user);
        $this->assertEquals(1, count($transaction->getInvalid()));
    }
}
