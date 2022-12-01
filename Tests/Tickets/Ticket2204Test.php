<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket2204Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_2204_Model';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine1\Manager::getInstance()->setValidate(\Doctrine1\Core::VALIDATE_ALL);
            $model               = new \Ticket_2204_Model();
            $model->test_decimal = '-123.456789';
            $model->save();

            \Doctrine1\Manager::getInstance()->setValidate(\Doctrine1\Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_2204_Model extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('test_decimal', 'decimal', 9, ['scale' => 6]);
        }
    }
}
