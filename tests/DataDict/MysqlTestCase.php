<?php
/* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Doctrine_DataDict_Mysql_TestCase
 *
 * @package  Doctrine
 * @author   Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license  http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category Object Relational Mapping
 * @link     www.doctrine-project.org
 * @since    1.0
 * @version  $Revision$
 */
class Doctrine_DataDict_Mysql_TestCase extends Doctrine_UnitTestCase
{
    public function testGetCharsetFieldDeclarationReturnsValidSql()
    {
        $this->assertEqual($this->dataDict->getCharsetFieldDeclaration('UTF-8'), 'CHARACTER SET UTF-8');
    }

    public function testGetCollationFieldDeclarationReturnsValidSql()
    {
        $this->assertEqual($this->dataDict->getCollationFieldDeclaration('xx'), 'COLLATE xx');
    }

    public function testGetPortableDeclarationSupportsNativeIntegerTypes()
    {
        $type = $this->dataDict->getPortableDeclaration(['type' => 'tinyint']);

        $this->assertEqual(
            $type,
            ['type'     => ['integer', 'boolean'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        // If column name starts with "is" or "has" treat as a boolean
        $type = $this->dataDict->getPortableDeclaration(['type' => 'tinyint', 'field' => 'isenabled']);

        $this->assertEqual(
            $type,
            ['type'     => ['boolean', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'smallint unsigned']);

        $this->assertEqual(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 2,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'mediumint unsigned']);

        $this->assertEqual(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 3,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'int unsigned']);

        $this->assertEqual(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 4,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'integer unsigned']);

        $this->assertEqual(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 4,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'bigint unsigned']);

        $this->assertEqual(
            $type,
            ['type'     => ['integer'],
                                        'length'   => 8,
                                        'unsigned' => true,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeStringTypes()
    {
        $type = $this->dataDict->getPortableDeclaration(['type' => 'text']);

        $this->assertEqual(
            $type,
            ['type'     => ['string', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'longtext']);

        $this->assertEqual(
            $type,
            ['type'     => ['string', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'mediumtext']);

        $this->assertEqual(
            $type,
            ['type'     => ['string', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'tinytext']);

        $this->assertEqual(
            $type,
            ['type'     => ['string', 'clob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => false]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'tinyint', 'comment' => 'BOOL']);

        $this->assertEqual(
            $type,
            ['type'     => ['boolean', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => false,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'tinyint', 'field' => 'hascontent']);

        $this->assertEqual(
            $type,
            ['type'     => ['boolean', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'tinyint']);

        $this->assertEqual(
            $type,
            ['type'     => ['integer', 'boolean'],
                                        'length'   => 1,
                                        'unsigned' => null,
            'fixed'    => false]
        );
    }

    public function testGetPortableDeclarationSupportsNativeFloatTypes()
    {
        $type = $this->dataDict->getPortableDeclaration(['type' => 'float']);

        $this->assertEqual(
            $type,
            ['type'     => ['float'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'real unsigned']);

        $this->assertEqual(
            $type,
            ['type'     => ['float'],
                                        'length'   => null,
                                        'unsigned' => true,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'double']);

        $this->assertEqual(
            $type,
            ['type'     => ['float'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeDateType()
    {
        $type = $this->dataDict->getPortableDeclaration(['type' => 'date']);

        $this->assertEqual(
            $type,
            ['type'     => ['date'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeDecimalTypes()
    {
        $type = $this->dataDict->getPortableDeclaration(['type' => 'decimal']);

        $this->assertEqual(
            $type,
            ['type'     => ['decimal'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'unknown']);

        $this->assertEqual(
            $type,
            ['type'     => ['decimal'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'numeric']);

        $this->assertEqual(
            $type,
            ['type'     => ['decimal'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeTimestampTypes()
    {
        $type = $this->dataDict->getPortableDeclaration(['type' => 'timestamp']);

        $this->assertEqual(
            $type,
            ['type'     => ['timestamp'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'datetime']);

        $this->assertEqual(
            $type,
            ['type'     => ['timestamp'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeYearType()
    {
        $type = $this->dataDict->getPortableDeclaration(['type' => 'year']);


        $this->assertEqual(
            $type,
            ['type'     => ['integer', 'date'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );
    }

    public function testGetPortableDeclarationSupportsNativeBlobTypes()
    {
        $type = $this->dataDict->getPortableDeclaration(['type' => 'blob']);

        $this->assertEqual(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'mediumblob']);

        $this->assertEqual(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'tinyblob']);

        $this->assertEqual(
            $type,
            ['type'     => ['blob'],
                                        'length'   => null,
                                        'unsigned' => null,
            'fixed'    => null]
        );

        $type = $this->dataDict->getPortableDeclaration(['type' => 'longblob']);

        $this->assertEqual(
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

        $type = $this->dataDict->getPortableDeclaration($field);

        $this->assertEqual(
            $type,
            ['type'     => ['enum', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
                                        'fixed'    => false,
            'values'   => ['a', 'b', 'c']]
        );

        $field['type'] = "set('a','b','c')";

        $type = $this->dataDict->getPortableDeclaration($field);

        $this->assertEqual(
            $type,
            ['type'     => ['set', 'integer'],
                                        'length'   => 5,
                                        'unsigned' => null,
                                        'fixed'    => false,
            'values'   => ['a', 'b', 'c']]
        );

        // Custom "boolean" type when ENUM only has two values
        $field['type'] = "enum('y','n')";

        $type = $this->dataDict->getPortableDeclaration($field);

        $this->assertEqual(
            $type,
            ['type'     => ['enum', 'boolean', 'integer'],
                                        'length'   => 1,
                                        'unsigned' => null,
                                        'fixed'    => false,
            'values'   => ['y', 'n']]
        );

        // Another special case where types are flipped when field name is "is" or "has"
        $field['field'] = 'isenabled';

        $type = $this->dataDict->getPortableDeclaration($field);

        $this->assertEqual(
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
        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(1)');

        // Native ENUM type still disabled, should still be VARCHAR
        // this test is here because there was an issue where SET type was used if the ATTR_USE_NATIVE_SET setting
        // was enabled but the ENUM one was not (due to an intentional case fall-through)
        $this->conn->setAttribute(Doctrine_Core::ATTR_USE_NATIVE_SET, true);
        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(1)');

        // Native type enabled
        $this->conn->setAttribute(Doctrine_Core::ATTR_USE_NATIVE_ENUM, true);
        $this->assertEqual($this->dataDict->getNativeDeclaration($a), "ENUM('a', 'b', 'c')");
    }

    public function testGetNativeDefinitionSupportsSetTypes()
    {
        $a = ['type' => 'set', 'fixed' => false, 'values' => ['a', 'b', 'c']];

        // Native SET type disabled, should be VARCHAR
        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(5)');

        // Enabling ENUM native type should have no effect on SET
        $this->conn->setAttribute(Doctrine_Core::ATTR_USE_NATIVE_ENUM, true);
        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(5)');

        // Native type enabled
        $this->conn->setAttribute(Doctrine_Core::ATTR_USE_NATIVE_SET, true);
        $this->assertEqual($this->dataDict->getNativeDeclaration($a), "SET('a', 'b', 'c')");
    }

    public function testGetNativeDefinitionSupportsIntegerType()
    {
        $a = ['type' => 'integer', 'length' => 20, 'fixed' => false];

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BIGINT');

        $a['length'] = 4;

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'INT');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'SMALLINT');
    }

    public function testGetNativeDeclarationSupportsFloatType()
    {
        $a = ['type' => 'float', 'length' => 20, 'fixed' => false];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'FLOAT(20, 2)');
    }

    public function testGetNativeDeclarationSupportsBooleanType()
    {
        $a = ['type' => 'boolean', 'fixed' => false];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TINYINT(1)');
    }

    public function testGetNativeDeclarationSupportsDateType()
    {
        $a = ['type' => 'date', 'fixed' => false];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'DATE');
    }

    public function testGetNativeDeclarationSupportsTimestampType()
    {
        $a = ['type' => 'timestamp', 'fixed' => false];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'DATETIME');
    }

    public function testGetNativeDeclarationSupportsTimeType()
    {
        $a = ['type' => 'time', 'fixed' => false];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TIME');
    }

    public function testGetNativeDeclarationSupportsClobType()
    {
        $a = ['type' => 'clob'];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'LONGTEXT');
    }

    public function testGetNativeDeclarationSupportsBlobType()
    {
        $a = ['type' => 'blob'];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'LONGBLOB');
    }

    public function testGetNativeDeclarationSupportsCharType()
    {
        $a = ['type' => 'char', 'length' => 10];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'CHAR(10)');
    }

    public function testGetNativeDeclarationSupportsVarcharType()
    {
        $a = ['type' => 'varchar', 'length' => 10];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'VARCHAR(10)');
    }

    public function testGetNativeDeclarationSupportsArrayType()
    {
        $a = ['type' => 'array', 'length' => 40];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TINYTEXT');
    }

    public function testGetNativeDeclarationSupportsStringType()
    {
        $a = ['type' => 'string'];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }

    public function testGetNativeDeclarationSupportsStringTypeWithLongLength()
    {
        $a = ['type' => 'string', 'length' => 2000];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }

    public function testGetNativeDeclarationSupportsArrayType2()
    {
        $a = ['type' => 'array'];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'LONGTEXT');
    }

    public function testGetNativeDeclarationSupportsObjectType()
    {
        $a = ['type' => 'object'];

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'LONGTEXT');
    }
}
