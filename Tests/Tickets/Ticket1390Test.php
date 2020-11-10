<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1390Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $user = new \User();

        $record1 = $user->getTable()->find(4);
        $record2 = \Doctrine_Core::getTable('User')->find(4);

        $this->assertSame($record1, $record2);
    }
}
