<?php
namespace Tests\Connection;

use Doctrine1\Connection\Exception;
use Doctrine1\Connection\Sqlite\ErrorCode;
use PDOException;
use Tests\DoctrineUnitTestCase;

class SqliteTest extends DoctrineUnitTestCase
{
    protected static $exc;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$dbh->query("PRAGMA foreign_keys=1");
        static::$dbh->query("CREATE TABLE testerrors1 (id int NOT NULL PRIMARY KEY)");
        static::$dbh->query("CREATE TABLE testerrors2 (name TEXT)");
        static::$dbh->query("INSERT INTO testerrors1 (id) VALUES (1)");
    }

    public function testQuoteIdentifier()
    {
        $id = static::$connection->quoteIdentifier("identifier", false);
        $this->assertEquals($id, '"identifier"');
    }

    public function testNoSuchTableErrorIsSupported()
    {
        try {
            static::$dbh->query("SELECT * FROM test1");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedTable::class, $e, $e::class);
        static::assertEquals(ErrorCode::ERROR, $e->getDriverCode());
        static::assertEquals("no such table: test1", $e->getMessage());
    }

    public function testNoSuchColumnErrorIsSupported()
    {
        try {
            static::$dbh->query("SELECT * FROM testerrors1 WHERE test1=1");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedColumn::class, $e, $e::class);
        static::assertEquals(ErrorCode::ERROR, $e->getDriverCode());
        static::assertEquals("no such column: test1", $e->getMessage());
    }

    public function testNoSuchIndexErrorIsSupported()
    {
        try {
            static::$dbh->query("DROP INDEX testerrors1.test1");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedObject::class, $e, $e::class);
        static::assertEquals(ErrorCode::ERROR, $e->getDriverCode());
        static::assertEquals("no such index: testerrors1.test1", $e->getMessage());
    }

    public function testConstraintViolationIsSupported()
    {
        try {
            static::$dbh->query("INSERT INTO testerrors1 (id) VALUES (1)");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\IntegrityConstraintViolation::class, $e, $e::class);
        static::assertEquals(ErrorCode::CONSTRAINT, $e->getDriverCode());
        static::assertEquals("UNIQUE constraint failed: testerrors1.id", $e->getMessage());
    }

    public function testNotNullConstraintErrorIsSupported()
    {
        try {
            static::$dbh->query("INSERT INTO testerrors1 (id) VALUES (NULL)");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\IntegrityConstraintViolation::class, $e, $e::class);
        static::assertEquals(ErrorCode::CONSTRAINT, $e->getDriverCode());
        static::assertEquals("NOT NULL constraint failed: testerrors1.id", $e->getMessage());
    }

    public function testColumnNotPresentInTablesErrorIsSupported2()
    {
        try {
            static::$dbh->query("SELECT t1.id FROM testerrors1 t1 INNER JOIN testerrors2 t2 USING (id)");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedColumn::class, $e, $e::class);
        static::assertEquals(ErrorCode::ERROR, $e->getDriverCode());
        static::assertEquals("cannot join using column id - column not present in both tables", $e->getMessage());
    }

    public function testSyntaxErrorIsSupported()
    {
        try {
            static::$dbh->query("bogus sql");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\SyntaxError::class, $e, $e::class);
        static::assertEquals(ErrorCode::ERROR, $e->getDriverCode());
        static::assertEquals('near "bogus": syntax error', $e->getMessage());
    }

    public function testValueCountOnRowErrorIsSupported()
    {
        try {
            static::$dbh->query("INSERT INTO testerrors1 (id) VALUES (1, 2)");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\SyntaxError::class, $e, $e::class);
        static::assertEquals(ErrorCode::ERROR, $e->getDriverCode());
        static::assertEquals("2 values for 1 columns", $e->getMessage());
    }
}
