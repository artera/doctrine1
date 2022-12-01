<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1783Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1783';
            parent::prepareTables();
        }

        public function testValidateLargeIntegers()
        {
            static::$manager->setValidate(\Doctrine1\Core::VALIDATE_ALL);

            $test         = new \Ticket_1783();
            $test->bigint = PHP_INT_MAX + 1;

            $this->assertTrue($test->isValid());

            static::$manager->setValidate(\Doctrine1\Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1783 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('bigint', 'integer', null, ['type' => 'integer', 'unsigned' => true]);
        }
    }
}
