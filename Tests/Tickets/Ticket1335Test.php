<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1335Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $this->expectException(\Doctrine_Query_Exception::class);
        \Doctrine_Query::create()->execute();
    }
}
