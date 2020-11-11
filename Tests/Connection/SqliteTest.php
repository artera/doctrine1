<?php
namespace Tests\Connection;

use Tests\DoctrineUnitTestCase;

class SqliteTest extends DoctrineUnitTestCase
{
    protected static $exc;
    protected static ?string $driverName = 'Sqlite';

    public static function setUpBeforeClass(): void
    {
        static::$exc = new \Doctrine_Connection_Sqlite_Exception();
        parent::setUpBeforeClass();
    }

    public function testQuoteIdentifier()
    {
        $id = static::$connection->quoteIdentifier('identifier', false);
        $this->assertEquals($id, '"identifier"');
    }

    public function testNoSuchTableErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'no such table: test1']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_NOSUCHTABLE);
    }

    public function testNoSuchIndexErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'no such index: test1']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_NOT_FOUND);
    }

    public function testUniquePrimaryKeyErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'PRIMARY KEY must be unique']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_CONSTRAINT);
    }

    public function testIsNotUniqueErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'is not unique']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_CONSTRAINT);
    }

    public function testColumnsNotUniqueErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'columns name, id are not unique']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_CONSTRAINT);
    }

    public function testUniquenessConstraintErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'uniqueness constraint failed']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_CONSTRAINT);
    }

    public function testNotNullConstraintErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'may not be NULL']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_CONSTRAINT_NOT_NULL);
    }

    public function testNoSuchFieldErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'no such column: column1']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_NOSUCHFIELD);
    }

    public function testColumnNotPresentInTablesErrorIsSupported2()
    {
        static::$exc->processErrorInfo([0,0, 'column not present in both tables']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_NOSUCHFIELD);
    }

    public function testNearSyntaxErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, 'near "SELECT FROM": syntax error']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_SYNTAX);
    }

    public function testValueCountOnRowErrorIsSupported()
    {
        static::$exc->processErrorInfo([0,0, '3 values for 2 columns']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine_Core::ERR_VALUE_COUNT_ON_ROW);
    }
}
