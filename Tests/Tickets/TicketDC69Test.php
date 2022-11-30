<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC69Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $user = \Doctrine1\Core::getTable('User')
            ->createQuery('u')
            ->limit(1)
            ->fetchOne();
        $user->link('Email', 4);
        $user->save();

        $this->assertEquals($user->Email->id, 4);
    }
}
