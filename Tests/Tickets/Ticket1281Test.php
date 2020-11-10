<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1281Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $users      = \Doctrine_Core::getTable('User')->findAll();
        $user       = $users->getFirst();
        $user->name = 'zYne-';

        // new \values
        $this->assertEquals($user->getModified(), ['name' => 'zYne-']);

        // old values
        $this->assertEquals($user->getModified(true), ['name' => 'zYne']);
    }
}
