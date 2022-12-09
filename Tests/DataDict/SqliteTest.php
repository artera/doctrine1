<?php

namespace Tests\DataDict;

use Doctrine1\Column;
use Doctrine1\Column\Type;
use Tests\DoctrineUnitTestCase;

class SqliteTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Sqlite';

    public function testBooleanMapsToBooleanType()
    {
        $this->assertDeclarationType('boolean', 'boolean');
    }

    public function testIntegersMapToIntegerType()
    {
        $this->assertDeclarationType('tinyint', ['integer', 'boolean']);
        $this->assertDeclarationType('smallint', 'integer');
        $this->assertDeclarationType('mediumint', 'integer');
        $this->assertDeclarationType('int', 'integer');
        $this->assertDeclarationType('integer', 'integer');
        $this->assertDeclarationType('serial', 'integer');
        $this->assertDeclarationType('bigint', 'integer');
        $this->assertDeclarationType('bigserial', 'integer');
    }

    public function testBlobsMapToBlobType()
    {
        $this->assertDeclarationType('tinyblob', 'blob');
        $this->assertDeclarationType('mediumblob', 'blob');
        $this->assertDeclarationType('longblob', 'blob');
        $this->assertDeclarationType('blob', 'blob');
    }

    public function testDecimalMapsToDecimal()
    {
        $this->assertDeclarationType('decimal', 'decimal');
        $this->assertDeclarationType('numeric', 'decimal');
    }

    public function testFloatRealAndDoubleMapToFloat()
    {
        $this->assertDeclarationType('float', 'float');
        $this->assertDeclarationType('double', 'float');
        $this->assertDeclarationType('real', 'float');
    }

    public function testYearMapsToIntegerAndDate()
    {
        $this->assertDeclarationType('year', ['integer','date']);
    }

    public function testGetNativeDefinitionSupportsIntegerType()
    {
        $a = new Column('_', Type::Integer, length: 20, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INTEGER');

        $a = new Column('_', Type::Integer, length: 4, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INTEGER');

        $a = new Column('_', Type::Integer, length: 2, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INTEGER');
    }

    public function testGetNativeDefinitionSupportsFloatType()
    {
        $a = new Column('_', Type::Float, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'DOUBLE');
    }

    public function testGetNativeDefinitionSupportsBooleanType()
    {
        $a = new Column('_', Type::Boolean, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INTEGER');
    }

    public function testGetNativeDefinitionSupportsDateType()
    {
        $a = new Column('_', Type::Date, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'DATE');
    }

    public function testGetNativeDefinitionSupportsTimestampType()
    {
        $a = new Column('_', Type::Timestamp, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'DATETIME');
    }

    public function testGetNativeDefinitionSupportsTimeType()
    {
        $a = new Column('_', Type::Time, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TIME');
    }

    public function testGetNativeDefinitionSupportsBlobType()
    {
        $a = new Column('_', Type::BLOB);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'LONGBLOB');
    }

    public function testGetNativeDefinitionSupportsCharType()
    {
        $a = new Column('_', Type::String, 10, fixed: true);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'CHAR(10)');
    }

    public function testGetNativeDefinitionSupportsVarcharType()
    {
        $a = new Column('_', Type::String, 10);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'VARCHAR(10)');
    }

    public function testGetNativeDefinitionSupportsArrayType()
    {
        $a = new Column('_', Type::Array, 40);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'VARCHAR(40)');
    }

    public function testGetNativeDefinitionSupportsStringType()
    {
        $a = new Column('_', Type::String);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TEXT');
    }

    public function testGetNativeDefinitionSupportsArrayType2()
    {
        $a = new Column('_', Type::Array);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TEXT');
    }

    public function testGetNativeDefinitionSupportsObjectType()
    {
        $a = new Column('_', Type::Object);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TEXT');
    }
}
