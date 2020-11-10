<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC221Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testTest()
    {
        $migration1 = new \Doctrine_Migration(__DIR__ . '/DC221');
        $migration2 = new \Doctrine_Migration(__DIR__ . '/DC221');
        $this->assertEquals($migration1->getMigrationClasses(), $migration2->getMigrationClasses());
    }
}
