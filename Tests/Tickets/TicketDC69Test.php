<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC69Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testTest()
    {
        $user = \Doctrine::getTable('User')
            ->createQuery('u')
            ->limit(1)
            ->fetchOne();
        $user->link('Email', 4);
        $user->save();

        $this->assertEquals($user->Email->id, 4);
    }
}
