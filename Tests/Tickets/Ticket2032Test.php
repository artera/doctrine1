<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2032Test extends DoctrineUnitTestCase
{
    public function testNonSpacedOrderByIsParsedCorrectly()
    {
        \Doctrine1\Query::create()
            ->select('u.*')
            ->from('User u')
            ->orderby('u.name,u.id,u.password')
            ->execute();
    }
}
