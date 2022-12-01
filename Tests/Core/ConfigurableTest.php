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
        $this->assertEquals(static::$manager->getIndexNameFormat(), '%s_idx');
    }

    public function testGetSequenceNameFormatAttribute()
    {
        // default sequence name format is %_seq
        $this->assertEquals(static::$manager->getSequenceNameFormat(), '%s_seq');
    }

    public function testSetIndexNameFormatAttribute()
    {
        $original = static::$manager->getIndexNameFormat();
        static::$manager->setIndexNameFormat('%_index');

        $this->assertEquals(static::$manager->getIndexNameFormat(), '%_index');
        static::$manager->setIndexNameFormat($original);
    }

    public function testSetSequenceNameFormatAttribute()
    {
        $original = static::$manager->getSequenceNameFormat();
        static::$manager->setSequenceNameFormat('%_sequence');

        $this->assertEquals(static::$manager->getSequenceNameFormat(), '%_sequence');
        static::$manager->setSequenceNameFormat($original);
    }

    public function testExceptionIsThrownWhenSettingIndexNameFormatAttributeAtTableLevel()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->getTable('Entity')->setIndexNameFormat('%s_idx');
    }

    public function testExceptionIsThrownWhenSettingSequenceNameFormatAttributeAtTableLevel()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->getTable('Entity')->setSequenceNameFormat('%s_seq');
    }

    public function testDefaultQuoteIdentifierAttributeValueIsFalse()
    {
        $this->assertEquals(static::$manager->getQuoteIdentifier(), false);
    }

    public function testDefaultSequenceColumnNameAttributeValueIsId()
    {
        $this->assertEquals(static::$manager->getSequenceColumnName(), 'id');
    }

    public function testSequenceColumnNameAttributeAcceptsStrings()
    {
        $original = static::$manager->getSequenceColumnName();
        static::$manager->setSequenceColumnName('sequence');

        $this->assertEquals(static::$manager->getSequenceColumnName(), 'sequence');
        static::$manager->setSequenceColumnName($original);
    }

    public function testValidatorAttributeAcceptsBooleans()
    {
        static::$manager->setValidate(true);

        $this->assertEquals(static::$manager->getValidate(), true);
        static::$manager->setValidate(false);
    }

    public function testDefaultPortabilityAttributeValueIsAll()
    {
        $this->assertEquals(static::$manager->getPortability(), \Doctrine1\Core::PORTABILITY_NONE);
    }

    public function testPortabilityAttributeAcceptsPortabilityConstants()
    {
        static::$manager->setPortability(\Doctrine1\Core::PORTABILITY_RTRIM | \Doctrine1\Core::PORTABILITY_FIX_CASE);

        $this->assertEquals(
            static::$manager->getPortability(),
            \Doctrine1\Core::PORTABILITY_RTRIM | \Doctrine1\Core::PORTABILITY_FIX_CASE
        );
        static::$manager->setPortability(\Doctrine1\Core::PORTABILITY_ALL);
    }

    public function testDefaultListenerIsDoctrineEventListener()
    {
        $this->assertTrue(static::$manager->getListener()instanceof \Doctrine1\EventListener);
    }

    public function testListenerAttributeAcceptsEventListenerObjects()
    {
        $original = static::$manager->getListener();
        static::$manager->setListener(new \Doctrine1\EventListener());

        $this->assertTrue(static::$manager->getListener() instanceof \Doctrine1\EventListener);
        static::$manager->setListener($original);
    }

    public function testCollectionKeyAttributeAcceptsValidColumnName()
    {
        $original = static::$connection->getTable('User')->getCollectionKey();
        static::$connection->getTable('User')->setCollectionKey('name');
        static::$connection->getTable('User')->setCollectionKey($original);
    }

    public function testSettingInvalidColumnNameToCollectionKeyAttributeThrowsException()
    {
        $this->expectException(\Doctrine1\Exception::class);
        static::$connection->getTable('User')->setCollectionKey('unknown');
    }
}
