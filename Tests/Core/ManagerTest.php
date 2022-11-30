<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class ManagerTest extends DoctrineUnitTestCase
{
    public function testGetInstance()
    {
        $this->assertTrue(\Doctrine1\Manager::getInstance() instanceof \Doctrine1\Manager);
    }

    public function testOpenConnection()
    {
        $this->assertTrue(static::$connection instanceof \Doctrine1\Connection);
    }

    public function testGetIterator()
    {
        $this->assertTrue(static::$manager->getIterator() instanceof \ArrayIterator);
    }

    public function testCount()
    {
        $this->assertTrue(is_integer(count(static::$manager)));
    }

    public function testGetCurrentConnection()
    {
        $this->assertTrue(static::$manager->getCurrentConnection() === static::$connection);
    }

    public function testGetConnections()
    {
        $this->assertTrue(is_integer(count(static::$manager->getConnections())));
    }

    public function testClassifyTableize()
    {
        $name = 'Forum_Category';
        $this->assertEquals(\Doctrine1\Inflector::tableize($name), 'forum__category');
        $this->assertEquals(\Doctrine1\Inflector::classify(\Doctrine1\Inflector::tableize($name)), $name);
    }

    public function testDsnParser()
    {
        $mysql            = 'mysql://user:pass@localhost/dbname';
        $mysqlWithCharset = 'mysql://user:pass@localhost/dbname?charset=utf8';
        $sqlite           = 'sqlite:////full/unix/path/to/file.db';
        $sqlitewin        = 'sqlite:///c:/full/windows/path/to/file.db';
        $sqlitewin2       = 'sqlite:///D:\full\windows\path\to\file.db';

        $manager = \Doctrine1\Manager::getInstance();

        $res              = $manager->parseDsn($mysql);
        $expectedMysqlDsn = [
            'scheme'   => 'mysql',
            'host'     => 'localhost',
            'user'     => 'user',
            'pass'     => 'pass',
            'path'     => '/dbname',
            'dsn'      => 'mysql:host=localhost;dbname=dbname',
            'port'     => null,
            'query'    => null,
            'fragment' => null,
            'database' => 'dbname'];
        $this->assertEquals($expectedMysqlDsn, $res);

        $res              = $manager->parseDsn($mysqlWithCharset);
        $expectedMysqlDsn = [
            'scheme'   => 'mysql',
            'host'     => 'localhost',
            'user'     => 'user',
            'pass'     => 'pass',
            'path'     => '/dbname',
            'dsn'      => 'mysql:host=localhost;dbname=dbname;charset=utf8',
            'port'     => null,
            'query'    => null,
            'fragment' => null,
            'database' => 'dbname'];
        $this->assertEquals($expectedMysqlDsn, $res);

        $expectedDsn = [
            'scheme'   => 'sqlite',
            'host'     => null,
            'user'     => null,
            'pass'     => null,
            'path'     => '/full/unix/path/to/file.db',
            'dsn'      => 'sqlite:/full/unix/path/to/file.db',
            'port'     => null,
            'query'    => null,
            'fragment' => null,
            'database' => '/full/unix/path/to/file.db'];

        $res = $manager->parseDsn($sqlite);
        $this->assertEquals($expectedDsn, $res);

        $expectedDsn = [
            'scheme'   => 'sqlite',
            'host'     => null,
            'path'     => 'c:/full/windows/path/to/file.db',
            'dsn'      => 'sqlite:c:/full/windows/path/to/file.db',
            'port'     => null,
            'user'     => null,
            'pass'     => null,
            'query'    => null,
            'fragment' => null,
            'database' => 'c:/full/windows/path/to/file.db'];
        $res = $manager->parseDsn($sqlitewin);
        $this->assertEquals($expectedDsn, $res);

        $expectedDsn = [
            'scheme'   => 'sqlite',
            'host'     => null,
            'path'     => 'D:/full/windows/path/to/file.db',
            'dsn'      => 'sqlite:D:/full/windows/path/to/file.db',
            'port'     => null,
            'user'     => null,
            'pass'     => null,
            'query'    => null,
            'fragment' => null,
            'database' => 'D:/full/windows/path/to/file.db'];
        $res = $manager->parseDsn($sqlitewin2);
        $this->assertEquals($expectedDsn, $res);
    }

    public function testCreateDatabases(): array
    {
        // We need to know if we're under Windows or *NIX
        $OS = strtoupper(substr(PHP_OS, 0, 3));

        $tmp_dir = ($OS == 'WIN') ? str_replace('\\', '/', sys_get_temp_dir()) : '/tmp';

        $conn1 = \Doctrine1\Manager::connection("sqlite:///$tmp_dir/doctrin1.db", 'doctrine1');
        $conn2 = \Doctrine1\Manager::connection("sqlite:///$tmp_dir/doctrin2.db", 'doctrine2');

        $result1 = $conn1->createDatabase();
        $result2 = $conn2->createDatabase();

        return [$conn1, $conn2];
    }

    /**
     * @depends testCreateDatabases
     */
    public function testDropDatabases(array $connections)
    {
        foreach ($connections as $conn) {
            $conn->dropDatabase();
        }
    }

    public function testConnectionInformationDecoded()
    {
        $dsn = 'mysql://' . urlencode('test/t') . ':' . urlencode('p@ssword') . '@localhost/' . urlencode('db/name');

        $conn    = \Doctrine1\Manager::connection($dsn);
        $options = $conn->getOptions();

        $this->assertEquals($options['username'], 'test/t');
        $this->assertEquals($options['password'], 'p@ssword');
        $this->assertEquals($options['dsn'], 'mysql:host=localhost;dbname=db/name');
    }

    public static function prepareData(): void
    {
    }

    public static function prepareTables(): void
    {
    }
}
