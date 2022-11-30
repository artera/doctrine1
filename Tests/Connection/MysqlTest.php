<?php
namespace Tests\Connection;

use Tests\DoctrineUnitTestCase;

class MysqlTest extends DoctrineUnitTestCase
{
    protected static $exc;
    protected static ?string $driverName = 'Mysql';

    public static function setUpBeforeClass(): void
    {
        static::$exc = new \Doctrine1\Connection\Mysql\Exception();
        parent::setUpBeforeClass();
    }

    public function testQuoteIdentifier()
    {
        $id = static::$connection->quoteIdentifier('identifier', false);
        $this->assertEquals($id, '`identifier`');
    }

    public function testNotLockedErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1100, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOT_LOCKED);
    }

    public function testNotFoundErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1091, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOT_FOUND);
    }

    public function testSyntaxErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1064, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_SYNTAX);
    }

    public function testNoSuchDbErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1049, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHDB);
    }

    public function testNoSuchFieldErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1054, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHFIELD);
    }

    public function testNoSuchTableErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1051, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHTABLE);
    }

    public function testNoSuchTableErrorIsSupported2()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1146, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHTABLE);
    }

    public function testConstraintErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1048, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CONSTRAINT);
    }

    public function testConstraintErrorIsSupported2()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1216, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CONSTRAINT);
    }

    public function testConstraintErrorIsSupported3()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1217, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CONSTRAINT);
    }

    public function testNoDbSelectedErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1046, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NODBSELECTED);
    }

    public function testAccessViolationErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1142, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ACCESS_VIOLATION);
    }

    public function testAccessViolationErrorIsSupported2()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1044, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ACCESS_VIOLATION);
    }

    public function testCannotDropErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1008, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CANNOT_DROP);
    }

    public function testCannotCreateErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1004, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CANNOT_CREATE);
    }

    public function testCannotCreateErrorIsSupported2()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1005, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CANNOT_CREATE);
    }

    public function testCannotCreateErrorIsSupported3()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1006, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CANNOT_CREATE);
    }

    public function testAlreadyExistsErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1007, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ALREADY_EXISTS);
    }

    public function testAlreadyExistsErrorIsSupported2()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1022, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ALREADY_EXISTS);
    }

    public function testAlreadyExistsErrorIsSupported3()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1050, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ALREADY_EXISTS);
    }

    public function testAlreadyExistsErrorIsSupported4()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1061, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ALREADY_EXISTS);
    }

    public function testAlreadyExistsErrorIsSupported5()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1062, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ALREADY_EXISTS);
    }

    public function testValueCountOnRowErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 1136, '']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_VALUE_COUNT_ON_ROW);
    }
}
