<?php

namespace Tests\DataDict;

use Doctrine1\Column;
use Doctrine1\Column\Type;
use Tests\DoctrineUnitTestCase;

class PgsqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Pgsql';

    public function getDeclaration($type)
    {
        return static::$connection->dataDict->getPortableDeclaration(['type' => $type, 'name' => 'colname', 'length' => 2, 'fixed' => true]);
    }

    public function testGetPortableDeclarationSupportsNativeBlobTypes()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'blob']);

        $this->assertEquals(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'tinyblob']);

        $this->assertEquals(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'mediumblob']);

        $this->assertEquals(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'longblob']);

        $this->assertEquals(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'bytea']);

        $this->assertEquals(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'oid']);

        $this->assertEquals(
            $type,
            ['type'     => ['blob', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeTimestampTypes()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'timestamp']);

        $this->assertEquals(
            $type,
            ['type'     => ['timestamp'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'datetime']);

        $this->assertEquals(
            $type,
            ['type'     => ['timestamp'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeDecimalTypes()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'decimal']);

        $this->assertEquals(
            $type,
            ['type'     => ['decimal'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'money']);

        $this->assertEquals(
            $type,
            ['type'     => ['decimal'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'numeric']);

        $this->assertEquals(
            $type,
            ['type'     => ['decimal'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeFloatTypes()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'float']);

        $this->assertEquals(
            $type,
            ['type'     => ['float'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'double']);

        $this->assertEquals(
            $type,
            ['type'     => ['float'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'real']);

        $this->assertEquals(
            $type,
            ['type'     => ['float'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeYearType()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'year']);

        $this->assertEquals(
            $type,
            ['type'     => ['integer', 'date'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeDateType()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'date']);

        $this->assertEquals(
            $type,
            ['type'     => ['date'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeTimeType()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'time']);

        $this->assertEquals(
            $type,
            ['type'     => ['time'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeStringTypes()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'text']);

        $this->assertEquals(
            $type,
            ['type'     => ['string', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'interval']);

        $this->assertEquals(
            $type,
            ['type'     => ['string'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'varchar', 'length' => 1]);

        $this->assertEquals(
            $type,
            ['type'     => ['string', 'boolean'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'unknown', 'length' => 1]);

        $this->assertEquals(
            $type,
            ['type'     => ['string', 'boolean'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => true]
        );


        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'char', 'length' => 1]);

        $this->assertEquals(
            $type,
            ['type'     => ['string', 'boolean'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => true]
        );


        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'bpchar', 'length' => 1]);

        $this->assertEquals(
            $type,
            ['type'     => ['string', 'boolean'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => true]
        );
    }

    public function testGetPortableDeclarationSupportsNativeIntegerTypes()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'smallint']);

        $this->assertEquals($this->getDeclaration('smallint'), ['type' => ['integer', 'boolean'], 'length' => 2, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('int2'), ['type' => ['integer', 'boolean'], 'length' => 2, 'unsigned' => false, 'fixed' => null]);

        $this->assertEquals($this->getDeclaration('int'), ['type' => ['integer'], 'length' => 4, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('int4'), ['type' => ['integer'], 'length' => 4, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('integer'), ['type' => ['integer'], 'length' => 4, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('serial'), ['type' => ['integer'], 'length' => 4, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('serial4'), ['type' => ['integer'], 'length' => 4, 'unsigned' => false, 'fixed' => null]);

        $this->assertEquals($this->getDeclaration('bigint'), ['type' => ['integer'], 'length' => 8, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('int8'), ['type' => ['integer'], 'length' => 8, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('bigserial'), ['type' => ['integer'], 'length' => 8, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('serial8'), ['type' => ['integer'], 'length' => 8, 'unsigned' => false, 'fixed' => null]);
    }

    public function testGetPortableDeclarationSupportsNativeBooleanTypes()
    {
        $this->assertEquals($this->getDeclaration('bool'), ['type' => ['boolean'], 'length' => 1, 'unsigned' => false, 'fixed' => null]);
        $this->assertEquals($this->getDeclaration('boolean'), ['type' => ['boolean'], 'length' => 1, 'unsigned' => false, 'fixed' => null]);
    }

    public function testGetNativeDefinitionSupportsIntegerType()
    {
        $a = new Column('_', Type::Integer, length: 20, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'BIGINT');

        $a = new Column('_', Type::Integer, length: 4, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'INT');

        $a = new Column('_', Type::Integer, length: 2, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'SMALLINT');
    }

    public function testGetNativeDefinitionSupportsIntegerTypeWithAutoinc()
    {
        $a = new Column('_', Type::Integer, length: 20, fixed: false, autoincrement: true);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'BIGSERIAL');

        $a = new Column('_', Type::Integer, length: 4, fixed: false, autoincrement: true);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'SERIAL');

        $a = new Column('_', Type::Integer, length: 2, fixed: false, autoincrement: true);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'SERIAL');
    }

    public function testGetNativeDefinitionSupportsFloatType()
    {
        $a = new Column('_', Type::Float, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'FLOAT');
    }

    public function testGetNativeDefinitionSupportsBooleanType()
    {
        $a = new Column('_', Type::Boolean, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'BOOLEAN');
    }

    public function testGetNativeDefinitionSupportsDateType()
    {
        $a = new Column('_', Type::Date, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'DATE');
    }

    public function testGetNativeDefinitionSupportsTimestampType()
    {
        $a = new Column('_', Type::Timestamp, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TIMESTAMP');
    }

    public function testGetNativeDefinitionSupportsTimeType()
    {
        $a = new Column('_', Type::Time, fixed: false);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'TIME');
    }

    public function testGetNativeDefinitionSupportsBlobType()
    {
        $a = new Column('_', Type::BLOB);

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'BYTEA');
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
