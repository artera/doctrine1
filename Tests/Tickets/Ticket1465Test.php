<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1465Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $this->expectException(\Doctrine_Hydrator_Exception::class);
        \Doctrine_Query::create()
            ->from('User u, Phonenumber p')
            ->fetchArray();
    }
}
