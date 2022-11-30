<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1465Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $this->expectException(\Doctrine1\Hydrator\Exception::class);
        \Doctrine1\Query::create()
            ->from('User u, Phonenumber p')
            ->fetchArray();
    }
}
