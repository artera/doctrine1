<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class ConnectionTest extends DoctrineUnitTestCase
{
    public function testUnknownModule()
    {
        $this->expectException(\Doctrine_Connection_Exception::class);
        static::$connection->unknown;
    }

    public function testGetModule()
    {
        $this->assertTrue(static::$connection->unitOfWork instanceof \Doctrine_Connection_UnitOfWork);
        //$this->assertTrue(static::$connection->dataDict instanceof \Doctrine_DataDict);
        $this->assertTrue(static::$connection->expression instanceof \Doctrine_Expression_Driver);
        $this->assertTrue(static::$connection->transaction instanceof \Doctrine_Transaction);
        $this->assertTrue(static::$connection->export instanceof \Doctrine_Export);
    }

    public function testFetchAll()
    {
        static::$conn->exec('DROP TABLE entity');
        static::$conn->exec('CREATE TABLE entity (id INT, name TEXT)');

        static::$conn->exec("INSERT INTO entity (id, name) VALUES (1, 'zYne')");
        static::$conn->exec("INSERT INTO entity (id, name) VALUES (2, 'John')");

        $a = static::$conn->fetchAll('SELECT * FROM entity');


        $this->assertEquals(
            $a,
            [
                            0 => [
                              'id'   => '1',
                              'name' => 'zYne',
                            ],
                            1 => [
                              'id'   => '2',
                              'name' => 'John',
                            ],
            ]
        );
    }

    public function testFetchOne()
    {
        $c = static::$conn->fetchOne('SELECT COUNT(1) FROM entity');

        $this->assertEquals($c, 2);

        $c = static::$conn->fetchOne('SELECT COUNT(1) FROM entity WHERE id = ?', [1]);

        $this->assertEquals($c, 1);
    }


    public function testFetchColumn()
    {
        $a = static::$conn->fetchColumn('SELECT * FROM entity');

        $this->assertEquals(
            $a,
            [
                              0 => '1',
                              1 => '2',
            ]
        );

        $a = static::$conn->fetchColumn('SELECT * FROM entity WHERE id = ?', [1]);

        $this->assertEquals(
            $a,
            [
                              0 => '1',
            ]
        );
    }

    public function testFetchArray()
    {
        $a = static::$conn->fetchArray('SELECT * FROM entity');

        $this->assertEquals(
            $a,
            [
                              0 => '1',
                              1 => 'zYne',
            ]
        );

        $a = static::$conn->fetchArray('SELECT * FROM entity WHERE id = ?', [1]);

        $this->assertEquals(
            $a,
            [
                              0 => '1',
                              1 => 'zYne',
            ]
        );
    }

    public function testFetchRow()
    {
        $c = static::$conn->fetchRow('SELECT * FROM entity');

        $this->assertEquals(
            $c,
            [
                              'id'   => '1',
                              'name' => 'zYne',
            ]
        );

        $c = static::$conn->fetchRow('SELECT * FROM entity WHERE id = ?', [1]);

        $this->assertEquals(
            $c,
            [
                              'id'   => '1',
                              'name' => 'zYne',
            ]
        );
    }

    public function testFetchPairs()
    {
        static::$conn->exec('DROP TABLE entity');
    }

    public function testGetManager()
    {
        $this->assertTrue(static::$connection->getManager() === static::$manager);
    }

    public function testDeleteOnTransientRecordIsIgnored()
    {
        $user = static::$connection->create('User');
        static::$connection->unitOfWork->delete($user);
    }

    public function testGetTable()
    {
        $table = static::$connection->getTable('Group');
        $this->assertTrue($table instanceof \Doctrine_Table);

        $this->expectException(\Doctrine_Exception::class);
        $table = static::$connection->getTable('Unknown');

        $table = static::$connection->getTable('User');
        $this->assertTrue($table instanceof \UserTable);
    }

    public function testCreate()
    {
        $email = static::$connection->create('Email');
        $this->assertTrue($email instanceof \Email);
    }

    public function testGetDbh()
    {
        $this->assertTrue(static::$connection->getDbh() instanceof \PDO);
    }

    public function testCount()
    {
        $this->assertTrue(is_integer(count(static::$connection)));
    }

    public function testGetIterator()
    {
        $this->assertTrue(static::$connection->getIterator() instanceof \ArrayIterator);
    }

    public function testGetState()
    {
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine_Transaction::STATE_SLEEP);
        $this->assertEquals(\Doctrine_Lib::getConnectionStateAsString(static::$connection->transaction->getState()), 'open');
    }

    public function testGetTables()
    {
        $this->assertTrue(is_array(static::$connection->getTables()));
    }

    public function testRollback()
    {
        static::$connection->beginTransaction();
        $this->assertEquals(static::$connection->transaction->getTransactionLevel(), 1);
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine_Transaction::STATE_ACTIVE);
        static::$connection->rollback();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine_Transaction::STATE_SLEEP);
        $this->assertEquals(static::$connection->transaction->getTransactionLevel(), 0);
    }

    public function testNestedTransactions()
    {
        $this->assertEquals(static::$connection->transaction->getTransactionLevel(), 0);
        static::$connection->beginTransaction();
        $this->assertEquals(static::$connection->transaction->getTransactionLevel(), 1);
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine_Transaction::STATE_ACTIVE);
        static::$connection->beginTransaction();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine_Transaction::STATE_BUSY);
        $this->assertEquals(static::$connection->transaction->getTransactionLevel(), 2);
        static::$connection->commit();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine_Transaction::STATE_ACTIVE);
        $this->assertEquals(static::$connection->transaction->getTransactionLevel(), 1);
        static::$connection->commit();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine_Transaction::STATE_SLEEP);
        $this->assertEquals(static::$connection->transaction->getTransactionLevel(), 0);
    }

    public function testSqliteDsn()
    {
        $conn = \Doctrine_Manager::connection('sqlite:foo.sq3');
        $conn->connect();
        $conn->close();
        unlink('foo.sq3');
    }
}
