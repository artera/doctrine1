<?php

namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class ConnectionTest extends DoctrineUnitTestCase
{
    public function testUnknownModule(): void
    {
        $this->expectException(\Doctrine1\Connection\Exception::class);
        static::$connection->unknown;
    }

    public function testGetModule(): void
    {
        $this->assertTrue(static::$connection->unitOfWork instanceof \Doctrine1\Connection\UnitOfWork);
        //$this->assertTrue(static::$connection->dataDict instanceof \Doctrine1\DataDict);
        $this->assertTrue(static::$connection->expression instanceof \Doctrine1\Expression\Driver);
        $this->assertTrue(static::$connection->transaction instanceof \Doctrine1\Transaction);
        $this->assertTrue(static::$connection->export instanceof \Doctrine1\Export);
    }

    public function testFetchAll(): void
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

    public function testFetchOne(): void
    {
        $c = static::$conn->fetchOne('SELECT COUNT(1) FROM entity');

        $this->assertEquals($c, 2);

        $c = static::$conn->fetchOne('SELECT COUNT(1) FROM entity WHERE id = ?', [1]);

        $this->assertEquals($c, 1);
    }


    public function testFetchColumn(): void
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

    public function testFetchArray(): void
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

    public function testFetchRow(): void
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

    public function testFetchPairs(): void
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

    public function testGetTable(): void
    {
        $table = static::$connection->getTable('Group');
        $this->assertTrue($table instanceof \Doctrine1\Table);

        $this->expectException(\Error::class);
        $table = static::$connection->getTable('Unknown');

        $table = static::$connection->getTable('User');
        $this->assertTrue($table instanceof \UserTable);
    }

    public function testCreate(): void
    {
        $email = static::$connection->create('Email');
        $this->assertTrue($email instanceof \Email);
    }

    public function testGetDbh(): void
    {
        $this->assertTrue(static::$connection->getDbh() instanceof \PDO);
    }

    public function testCount(): void
    {
        $this->assertTrue(is_integer(count(static::$connection)));
    }

    public function testGetIterator(): void
    {
        $this->assertTrue(static::$connection->getIterator() instanceof \ArrayIterator);
    }

    public function testGetState(): void
    {
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
        $this->assertEquals('open', static::$connection->transaction->getState()->value);
    }

    public function testGetTables(): void
    {
        $this->assertTrue(is_array(static::$connection->getTables()));
    }

    public function testRollback(): void
    {
        static::$connection->beginTransaction();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine1\Transaction\State::ACTIVE);
        static::$connection->rollback();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
    }

    public function testNestedTransactions(): void
    {
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
        static::$connection->beginTransaction();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine1\Transaction\State::ACTIVE);
        static::$connection->beginTransaction();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine1\Transaction\State::BUSY);
        static::$connection->commit();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine1\Transaction\State::ACTIVE);
        static::$connection->commit();
        $this->assertEquals(static::$connection->transaction->getState(), \Doctrine1\Transaction\State::SLEEP);
    }

    public function testSqliteDsn(): void
    {
        $conn = \Doctrine1\Manager::connection('sqlite:foo.sq3');
        $conn->connect();
        $conn->close();
        unlink('foo.sq3');
    }
}
