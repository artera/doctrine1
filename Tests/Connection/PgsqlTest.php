<?php
namespace Tests\Connection;

use Tests\DoctrineUnitTestCase;
use Doctrine1\Connection\Exception;

class PgsqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = "Pgsql";

    public function testQuoteIdentifier()
    {
        $id = static::$connection->quoteIdentifier("identifier", false);
        $this->assertEquals('"identifier"', $id);
    }

    public function testExceptionInvalidCatalogName()
    {
        $pdo = new \PDO("pgsql:host=localhost");
        try {
            $pdo->query("select * from test");
        } catch (\PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedTable::class, $e);
        static::assertEquals(7, $e->getCode());
        static::assertEquals(
            'ERROR:  relation "test" does not exist
LINE 1: select * from test
                      ^',
            $e->getMessage()
        );
    }

    public function testExceptionClassOverride()
    {
        try {
            $pdo = new \PDO("pgsql:host=localhost;dbname=non_existing");
        } catch (\PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\ConnectionException\ConnectionFailure::class, $e);
        static::assertEquals(7, $e->getCode());
        static::assertEquals('connection to server at "localhost" (::1), port 5432 failed: FATAL:  database "non_existing" does not exist', $e->getMessage());
    }

    public function testExceptionSyntaxErrorOrAccessRuleViolation()
    {
        $pdo = new \PDO("pgsql:host=localhost;dbname=mtorromeo");
        try {
            $pdo->query("select * from test");
        } catch (\PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedTable::class, $e);
        static::assertEquals(7, $e->getCode());
        static::assertEquals(
            'ERROR:  relation "test" does not exist
LINE 1: select * from test
                      ^',
            $e->getMessage()
        );
    }

    public function testInvalidSyntaxError()
    {
        $pdo = new \PDO("pgsql:host=localhost;dbname=mtorromeo");
        try {
            $pdo->query("bogus sql");
        } catch (\PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\SyntaxError::class, $e);
        static::assertEquals(7, $e->getCode());
        static::assertEquals(
            'ERROR:  syntax error at or near "bogus"
LINE 1: bogus sql
        ^',
            $e->getMessage()
        );
    }
}
