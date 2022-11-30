<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2184Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $this->assertEquals(\Doctrine1\Inflector::classify('test_do$llar_sign'), 'TestDollarSign');
    }
}
