<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class ConfigurableTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
    }

    public static function prepareData(): void
    {
    }

    public function testGetIndexNameFormatAttribute()
    {
        // default index name format is %_idx
        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_IDXNAME_FORMAT), '%s_idx');
    }

    public function testGetSequenceNameFormatAttribute()
    {
        // default sequence name format is %_seq
        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_SEQNAME_FORMAT), '%s_seq');
    }

    public function testSetIndexNameFormatAttribute()
    {
        $original = static::$manager->getAttribute(\Doctrine1\Core::ATTR_IDXNAME_FORMAT);
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_IDXNAME_FORMAT, '%_index');

        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_IDXNAME_FORMAT), '%_index');
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_IDXNAME_FORMAT, $original);
    }

    public function testSetSequenceNameFormatAttribute()
    {
        $original = static::$manager->getAttribute(\Doctrine1\Core::ATTR_SEQNAME_FORMAT);
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_SEQNAME_FORMAT, '%_sequence');

        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_SEQNAME_FORMAT), '%_sequence');
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_SEQNAME_FORMAT, $original);
    }

    public function testExceptionIsThrownWhenSettingIndexNameFormatAttributeAtTableLevel()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->getTable('Entity')->setAttribute(\Doctrine1\Core::ATTR_IDXNAME_FORMAT, '%s_idx');
    }

    public function testExceptionIsThrownWhenSettingSequenceNameFormatAttributeAtTableLevel()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->getTable('Entity')->setAttribute(\Doctrine1\Core::ATTR_SEQNAME_FORMAT, '%s_seq');
    }

    public function testSettingFieldCaseIsSuccesfulWithZero()
    {
        $original = static::$connection->getAttribute(\Doctrine1\Core::ATTR_FIELD_CASE);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_FIELD_CASE, 0);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_FIELD_CASE, $original);
    }

    public function testSettingFieldCaseIsSuccesfulWithCaseConstants()
    {
        $original = static::$connection->getAttribute(\Doctrine1\Core::ATTR_FIELD_CASE);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_FIELD_CASE, CASE_LOWER);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_FIELD_CASE, $original);
    }

    public function testSettingFieldCaseIsSuccesfulWithCaseConstants2()
    {
        $original = static::$connection->getAttribute(\Doctrine1\Core::ATTR_FIELD_CASE);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_FIELD_CASE, CASE_UPPER);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_FIELD_CASE, $original);
    }

    public function testExceptionIsThrownWhenSettingFieldCaseToNotZeroOneOrTwo()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_FIELD_CASE, -1);
    }

    public function testExceptionIsThrownWhenSettingFieldCaseToNotZeroOneOrTwo2()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_FIELD_CASE, 5);
    }

    public function testDefaultQuoteIdentifierAttributeValueIsFalse()
    {
        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER), false);
    }

    public function testQuoteIdentifierAttributeAcceptsBooleans()
    {
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, true);

        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER), true);
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, false);
    }

    public function testDefaultSequenceColumnNameAttributeValueIsId()
    {
        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_SEQCOL_NAME), 'id');
    }

    public function testSequenceColumnNameAttributeAcceptsStrings()
    {
        $original = static::$manager->getAttribute(\Doctrine1\Core::ATTR_SEQCOL_NAME);
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_SEQCOL_NAME, 'sequence');

        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_SEQCOL_NAME), 'sequence');
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_SEQCOL_NAME, $original);
    }

    public function testValidatorAttributeAcceptsBooleans()
    {
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, true);

        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_VALIDATE), true);
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, false);
    }

    public function testDefaultPortabilityAttributeValueIsAll()
    {
        $this->assertEquals(static::$manager->getAttribute(\Doctrine1\Core::ATTR_PORTABILITY), \Doctrine1\Core::PORTABILITY_NONE);
    }

    public function testPortabilityAttributeAcceptsPortabilityConstants()
    {
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_PORTABILITY, \Doctrine1\Core::PORTABILITY_RTRIM | \Doctrine1\Core::PORTABILITY_FIX_CASE);

        $this->assertEquals(
            static::$manager->getAttribute(\Doctrine1\Core::ATTR_PORTABILITY),
            \Doctrine1\Core::PORTABILITY_RTRIM | \Doctrine1\Core::PORTABILITY_FIX_CASE
        );
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_PORTABILITY, \Doctrine1\Core::PORTABILITY_ALL);
    }

    public function testDefaultListenerIsDoctrineEventListener()
    {
        $this->assertTrue(static::$manager->getAttribute(\Doctrine1\Core::ATTR_LISTENER) instanceof \Doctrine1\EventListener);
    }

    public function testListenerAttributeAcceptsEventListenerObjects()
    {
        $original = static::$manager->getAttribute(\Doctrine1\Core::ATTR_LISTENER);
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_LISTENER, new \Doctrine1\EventListener());

        $this->assertTrue(static::$manager->getAttribute(\Doctrine1\Core::ATTR_LISTENER) instanceof \Doctrine1\EventListener);
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_LISTENER, $original);
    }

    public function testCollectionKeyAttributeAcceptsValidColumnName()
    {
        $original = static::$connection->getTable('User')->getAttribute(\Doctrine1\Core::ATTR_COLL_KEY);
        static::$connection->getTable('User')->setAttribute(\Doctrine1\Core::ATTR_COLL_KEY, 'name');
        static::$connection->getTable('User')->setAttribute(\Doctrine1\Core::ATTR_COLL_KEY, $original);
    }

    public function testSettingInvalidColumnNameToCollectionKeyAttributeThrowsException()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->getTable('User')->setAttribute(\Doctrine1\Core::ATTR_COLL_KEY, 'unknown');
    }

    public function testSettingCollectionKeyAttributeOnOtherThanTableLevelThrowsException()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->setAttribute(\Doctrine1\Core::ATTR_COLL_KEY, 'name');
    }

    public function testGetAttributes()
    {
        $this->assertTrue(is_array(static::$manager->getAttributes()));
    }
}
