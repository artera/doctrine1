<?php
namespace Tests\DataDict;

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
        $a = ['type' => 'integer', 'length' => 20, 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INTEGER');

        $a['length'] = 4;

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INTEGER');

        $a['length'] = 2;

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INTEGER');
    }

    public function testGetNativeDefinitionSupportsFloatType()
    {
        $a = ['type' => 'float', 'length' => 20, 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'DOUBLE');
    }

    public function testGetNativeDefinitionSupportsBooleanType()
    {
        $a = ['type' => 'boolean', 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INTEGER');
    }

    public function testGetNativeDefinitionSupportsDateType()
    {
        $a = ['type' => 'date', 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'DATE');
    }

    public function testGetNativeDefinitionSupportsTimestampType()
    {
        $a = ['type' => 'timestamp', 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'DATETIME');
    }

    public function testGetNativeDefinitionSupportsTimeType()
    {
        $a = ['type' => 'time', 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TIME');
    }

    public function testGetNativeDefinitionSupportsClobType()
    {
        $a = ['type' => 'clob'];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'LONGTEXT');
    }

    public function testGetNativeDefinitionSupportsBlobType()
    {
        $a = ['type' => 'blob'];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'LONGBLOB');
    }

    public function testGetNativeDefinitionSupportsCharType()
    {
        $a = ['type' => 'char', 'length' => 10];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'CHAR(10)');
    }

    public function testGetNativeDefinitionSupportsVarcharType()
    {
        $a = ['type' => 'varchar', 'length' => 10];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'VARCHAR(10)');
    }

    public function testGetNativeDefinitionSupportsArrayType()
    {
        $a = ['type' => 'array', 'length' => 40];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'VARCHAR(40)');
    }

    public function testGetNativeDefinitionSupportsStringType()
    {
        $a = ['type' => 'string'];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TEXT');
    }

    public function testGetNativeDefinitionSupportsArrayType2()
    {
        $a = ['type' => 'array'];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TEXT');
    }

    public function testGetNativeDefinitionSupportsObjectType()
    {
        $a = ['type' => 'object'];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TEXT');
    }
}
