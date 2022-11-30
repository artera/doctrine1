<?php
namespace Tests\Connection;

use Tests\DoctrineUnitTestCase;

class PgsqlTest extends DoctrineUnitTestCase
{
    protected static $exc;
    protected static ?string $driverName = 'Pgsql';

    public static function setUpBeforeClass(): void
    {
        static::$exc = new \Doctrine1\Connection\Pgsql\Exception();
        parent::setUpBeforeClass();
    }

    public function testQuoteIdentifier()
    {
        $id = static::$connection->quoteIdentifier('identifier', false);
        $this->assertEquals($id, '"identifier"');
    }

    public function testNoSuchTableErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'table test does not exist']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHTABLE);
    }

    public function testNoSuchTableErrorIsSupported2()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'relation does not exist']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHTABLE);
    }

    public function testNoSuchTableErrorIsSupported3()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'sequence does not exist']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHTABLE);
    }

    public function testNoSuchTableErrorIsSupported4()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'class xx not found']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHTABLE);
    }

    public function testSyntaxErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'parser: parse error at or near']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_SYNTAX);
    }

    public function testSyntaxErrorIsSupported2()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'syntax error at']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_SYNTAX);
    }

    public function testSyntaxErrorIsSupported3()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'column reference r.r is ambiguous']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_SYNTAX);
    }

    public function testInvalidNumberErrorIsSupported()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'pg_atoi: error in somewhere: can\'t parse ']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_INVALID_NUMBER);
    }

    public function testInvalidNumberErrorIsSupported2()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'value unknown is out of range for type bigint']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_INVALID_NUMBER);
    }

    public function testInvalidNumberErrorIsSupported3()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'integer out of range']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_INVALID_NUMBER);
    }

    public function testInvalidNumberErrorIsSupported4()
    {
        $this->assertTrue(static::$exc->processErrorInfo([0, 0, 'invalid input syntax for type integer']));
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_INVALID_NUMBER);
    }

    public function testNoSuchFieldErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'column name (of relation xx) does not exist']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHFIELD);
    }

    public function testNoSuchFieldErrorIsSupported2()
    {
        static::$exc->processErrorInfo([0, 0, 'attribute xx not found']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHFIELD);
    }

    public function testNoSuchFieldErrorIsSupported3()
    {
        static::$exc->processErrorInfo([0, 0, 'relation xx does not have attribute']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHFIELD);
    }

    public function testNoSuchFieldErrorIsSupported4()
    {
        static::$exc->processErrorInfo([0, 0, 'column xx specified in USING clause does not exist in left table']);

        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHFIELD);
    }

    public function testNoSuchFieldErrorIsSupported5()
    {
        static::$exc->processErrorInfo([0, 0, 'column xx specified in USING clause does not exist in right table']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOSUCHFIELD);
    }

    public function testNotFoundErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'index xx does not exist/']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_NOT_FOUND);
    }

    public function testNotNullConstraintErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'violates not-null constraint']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CONSTRAINT_NOT_NULL);
    }

    public function testConstraintErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'referential integrity violation']);

        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CONSTRAINT);
    }

    public function testConstraintErrorIsSupported2()
    {
        static::$exc->processErrorInfo([0, 0, 'violates xx constraint']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_CONSTRAINT);
    }

    public function testInvalidErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'value too long for type character']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_INVALID);
    }

    public function testAlreadyExistsErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'relation xx already exists']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ALREADY_EXISTS);
    }

    public function testDivZeroErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'division by zero']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_DIVZERO);
    }

    public function testDivZeroErrorIsSupported2()
    {
        static::$exc->processErrorInfo([0, 0, 'divide by zero']);

        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_DIVZERO);
    }

    public function testAccessViolationErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'permission denied']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_ACCESS_VIOLATION);
    }

    public function testValueCountOnRowErrorIsSupported()
    {
        static::$exc->processErrorInfo([0, 0, 'more expressions than target columns']);
        $this->assertEquals(static::$exc->getPortableCode(), \Doctrine1\Core::ERR_VALUE_COUNT_ON_ROW);
    }
}
