<?php
namespace Tests\DataDict;

use Tests\DoctrineUnitTestCase;

class MysqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Mysql';

    public function testGetCharsetFieldDeclarationReturnsValidSql()
    {
        $this->assertEquals(static::$connection->dataDict->getCharsetFieldDeclaration('UTF-8'), 'CHARACTER SET UTF-8');
    }

    public function testGetCollationFieldDeclarationReturnsValidSql()
    {
        $this->assertEquals(static::$connection->dataDict->getCollationFieldDeclaration('xx'), 'COLLATE xx');
    }

    public function testGetPortableDeclarationSupportsNativeIntegerTypes()
    {
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'tinyint']);

        $this->assertEquals(
            $type,
            ['type'     => ['integer', 'boolean'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        // If column name starts with "is" or "has" treat as a boolean
        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'tinyint', 'field' => 'isenabled']);

        $this->assertEquals(
            $type,
            ['type'     => ['boolean', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'smallint unsigned']);

        $this->assertEquals(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 2,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'mediumint unsigned']);

        $this->assertEquals(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 3,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'int unsigned']);

        $this->assertEquals(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 4,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'integer unsigned']);

        $this->assertEquals(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 4,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'bigint unsigned']);

        $this->assertEquals(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 8,
                                        'unsigned' => true,
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
            'fixed'    => false]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'longtext']);

        $this->assertEquals(
            $type,
            ['type'     => ['string', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'mediumtext']);

        $this->assertEquals(
            $type,
            ['type'     => ['string', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'tinytext']);

        $this->assertEquals(
            $type,
            ['type'     => ['string', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'tinyint', 'comment' => 'BOOL']);

        $this->assertEquals(
            $type,
            ['type'     => ['boolean', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => false,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'tinyint', 'field' => 'hascontent']);

        $this->assertEquals(
            $type,
            ['type'     => ['boolean', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'tinyint']);

        $this->assertEquals(
            $type,
            ['type'     => ['integer', 'boolean'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => false]
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

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'real unsigned']);

        $this->assertEquals(
            $type,
            ['type'     => ['float'],
                                        'length'   => null,
                                        'unsigned' => true,
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

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'unknown']);

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

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'mediumblob']);

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

        $type = static::$connection->dataDict->getPortableDeclaration(['type' => 'longblob']);

        $this->assertEquals(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeEnumTypes()
    {
        $field = [
            'field'   => 'letter',
            'type'    => "enum('a','b','c')",
            'null'    => 'NO',
            'key'     => '',
            'default' => 'a',
            'extra'   => ''
        ];

        $type = static::$connection->dataDict->getPortableDeclaration($field);

        $this->assertEquals(
            $type,
            ['type'     => ['enum', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
                                        'fixed'    => false,
            'values'   => ['a', 'b', 'c']]
        );

        $field['type'] = "set('a','b','c')";

        $type = static::$connection->dataDict->getPortableDeclaration($field);

        $this->assertEquals(
            $type,
            ['type'     => ['set', 'integer'],
                                        'length'   => 5,
                                        'unsigned' => null,
                                        'fixed'    => false,
            'values'   => ['a', 'b', 'c']]
        );

        // Custom "boolean" type when ENUM only has two values
        $field['type'] = "enum('y','n')";

        $type = static::$connection->dataDict->getPortableDeclaration($field);

        $this->assertEquals(
            $type,
            ['type'     => ['enum', 'boolean', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
                                        'fixed'    => false,
            'values'   => ['y', 'n']]
        );

        // Another special case where types are flipped when field name is "is" or "has"
        $field['field'] = 'isenabled';

        $type = static::$connection->dataDict->getPortableDeclaration($field);

        $this->assertEquals(
            $type,
            ['type'     => ['boolean', 'enum', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
                                        'fixed'    => false,
            'values'   => ['y', 'n']]
        );
    }

    public function testGetNativeDefinitionSupportsEnumTypes()
    {
        $a = ['type' => 'enum', 'fixed' => false, 'values' => ['a', 'b', 'c']];

        // Native ENUM type disabled, should be VARCHAR
        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_ENUM, false);
        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'VARCHAR(1)');

        // Native ENUM type still disabled, should still be VARCHAR
        // this test is here because there was an issue where SET type was used if the ATTR_USE_NATIVE_SET setting
        // was enabled but the ENUM one was not (due to an intentional case fall-through)
        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_SET, true);
        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'VARCHAR(1)');

        // Native type enabled
        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_ENUM, true);
        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), "ENUM('a', 'b', 'c')");
    }

    public function testGetNativeDefinitionSupportsSetTypes()
    {
        $a = ['type' => 'set', 'fixed' => false, 'values' => ['a', 'b', 'c']];

        // Native SET type disabled, should be VARCHAR
        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_SET, false);
        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'VARCHAR(5)');

        // Enabling ENUM native type should have no effect on SET
        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_ENUM, true);
        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'VARCHAR(5)');

        // Native type enabled
        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_SET, true);
        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), "SET('a', 'b', 'c')");
    }

    public function testGetNativeDefinitionSupportsIntegerType()
    {
        $a = ['type' => 'integer', 'length' => 20, 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->getNativeDeclaration($a), 'BIGINT');

        $a['length'] = 4;

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'INT');

        $a['length'] = 2;

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'SMALLINT');
    }

    public function testGetNativeDeclarationSupportsFloatType()
    {
        $a = ['type' => 'float', 'length' => 20, 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'FLOAT(20, 2)');
    }

    public function testGetNativeDeclarationSupportsBooleanType()
    {
        $a = ['type' => 'boolean', 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'TINYINT(1)');
    }

    public function testGetNativeDeclarationSupportsDateType()
    {
        $a = ['type' => 'date', 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'DATE');
    }

    public function testGetNativeDeclarationSupportsTimestampType()
    {
        $a = ['type' => 'timestamp', 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'DATETIME');
    }

    public function testGetNativeDeclarationSupportsTimeType()
    {
        $a = ['type' => 'time', 'fixed' => false];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'TIME');
    }

    public function testGetNativeDeclarationSupportsClobType()
    {
        $a = ['type' => 'clob'];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'LONGTEXT');
    }

    public function testGetNativeDeclarationSupportsBlobType()
    {
        $a = ['type' => 'blob'];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'LONGBLOB');
    }

    public function testGetNativeDeclarationSupportsCharType()
    {
        $a = ['type' => 'char', 'length' => 10];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'CHAR(10)');
    }

    public function testGetNativeDeclarationSupportsVarcharType()
    {
        $a = ['type' => 'varchar', 'length' => 10];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'VARCHAR(10)');
    }

    public function testGetNativeDeclarationSupportsArrayType()
    {
        $a = ['type' => 'array', 'length' => 40];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'TINYTEXT');
    }

    public function testGetNativeDeclarationSupportsStringType()
    {
        $a = ['type' => 'string'];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'TEXT');
    }

    public function testGetNativeDeclarationSupportsStringTypeWithLongLength()
    {
        $a = ['type' => 'string', 'length' => 2000];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'TEXT');
    }

    public function testGetNativeDeclarationSupportsArrayType2()
    {
        $a = ['type' => 'array'];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'LONGTEXT');
    }

    public function testGetNativeDeclarationSupportsObjectType()
    {
        $a = ['type' => 'object'];

        $this->assertEquals(static::$connection->dataDict->GetNativeDeclaration($a), 'LONGTEXT');
    }
}
