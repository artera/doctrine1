<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2032Test extends DoctrineUnitTestCase
{
    public function testNonSpacedOrderByIsParsedCorrectly()
    {
        \Doctrine_Query::create()
            ->select('u.*')
            ->from('User u')
            ->orderby('u.name,u.id,u.password')
            ->execute();
    }
}
