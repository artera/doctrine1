<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1335Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $this->expectException(\Doctrine1\Query\Exception::class);
        \Doctrine1\Query::create()->execute();
    }
}
