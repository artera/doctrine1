<?php
namespace Tests\Connection;

use Tests\DoctrineUnitTestCase;
use Doctrine1\Connection\Exception;
use Doctrine1\Connection\Mysql\ErrorCode;
use PDOException;

class MysqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = "Mysql";

    public function testQuoteIdentifier()
    {
        $id = static::$connection->quoteIdentifier("identifier", false);
        $this->assertEquals("`identifier`", $id);
    }

    public function testExceptionInvalidCatalogName()
    {
        $pdo = new \PDO(getenv("MYSQL_DSN"));
        try {
            $pdo->query("select * from test");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\InvalidCatalogName::class, $e, $e::class);
        static::assertEquals(ErrorCode::ER_NO_DB_ERROR, $e->getDriverCode());
        static::assertEquals("No database selected", $e->getMessage());
        static::assertEquals("000", $e->getSQLStateSubclass());
    }

    public function testExceptionClassOverride()
    {
        try {
            $pdo = new \PDO(getenv("MYSQL_DSN") . ";dbname=non_existing");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\InvalidCatalogName::class, $e, $e::class);
        static::assertEquals(ErrorCode::ER_BAD_DB_ERROR, $e->getDriverCode());
        static::assertEquals("Unknown database 'non_existing'", $e->getMessage());
        static::assertEquals("000", $e->getSQLStateSubclass());
    }

    public function testExceptionSyntaxErrorOrAccessRuleViolation()
    {
        $pdo = new \PDO(getenv("MYSQL_DSN") . ";dbname=intranet_test");
        try {
            $pdo->query("select * from test");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedTable::class, $e, $e::class);
        static::assertEquals(ErrorCode::ER_NO_SUCH_TABLE, $e->getDriverCode());
        static::assertEquals("Table 'intranet_test.test' doesn't exist", $e->getMessage());
        static::assertEquals("S02", $e->getSQLStateSubclass());
    }

    public function testInvalidSyntaxError()
    {
        $pdo = new \PDO(getenv("MYSQL_DSN") . ";dbname=intranet_test");
        try {
            $pdo->query("bogus sql");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation::class, $e, $e::class);
        static::assertEquals(ErrorCode::ER_PARSE_ERROR, $e->getDriverCode());
        static::assertEquals(
            "You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'bogus sql' at line 1",
            $e->getMessage()
        );
        static::assertEquals("000", $e->getSQLStateSubclass());
    }

    public function testColumnNotPresentInTablesErrorIsSupported2()
    {
        $pdo = new \PDO(getenv("MYSQL_DSN") . ";dbname=intranet_test");
        try {
            $pdo->query("SELECT t1.id FROM aziende t1 INNER JOIN operatori t2 USING (ragione_sociale)");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedColumn::class, $e, $e::class);
        static::assertEquals(ErrorCode::ER_BAD_FIELD_ERROR, $e->getDriverCode());
        static::assertEquals("Unknown column 'ragione_sociale' in 'from clause'", $e->getMessage());
    }

    public function testCantDropFieldOrKeyIsSupported()
    {
        $pdo = new \PDO(getenv("MYSQL_DSN") . ";dbname=intranet_test");
        try {
            $pdo->query("ALTER TABLE aziende DROP KEY non_existing");
        } catch (PDOException $e) {
            $e = Exception::fromPDO($e, static::$connection);
        }
        static::assertInstanceOf(Exception\SyntaxErrorOrAccessRuleViolation\UndefinedObject::class, $e, $e::class);
        static::assertEquals(ErrorCode::ER_CANT_DROP_FIELD_OR_KEY, $e->getDriverCode());
        static::assertEquals("Can't DROP 'non_existing'; check that column/key exists", $e->getMessage());
    }
}
