<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1520Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1520_Product';
            parent::prepareTables();
        }

        public function testTest()
        {
            $profiler = new \Doctrine1\Connection\Profiler();
            \Doctrine1\Manager::connection()->addListener($profiler);
            $price       = 200;
            $user        = new \Ticket_1520_Product();
            $user->title = 'test';
            $user->price = $price;
            $user->save();
            $id = $user->id;
            $user->free();

            $user        = \Doctrine1\Core::getTable('Ticket_1520_Product')->find($id);
            $user->price = $price;
            $this->assertEquals($user->getModified(), []);
        }
    }
}

namespace {
    class Ticket_1520_Product extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('title', 'string', 255);
            $this->hasColumn('price', 'decimal');
        }
    }
}
