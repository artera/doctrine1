<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1417Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $user       = new \User();
        $user->name = 'jwagejon';
        $this->assertEquals($user->getModified(), ['name' => 'jwagejon']);
        $this->assertEquals($user->getModified(true), ['name' => null]);
        $user->save();
        $this->assertEquals($user->getModified(), []);
        $this->assertEquals($user->getModified(true), []);
        $this->assertEquals($user->getLastModified(), ['name' => 'jwagejon', 'type' => 0]);
        $this->assertEquals($user->getLastModified(true), ['name' => null, 'type' => null]);
        $user->name = 'jon';
        $this->assertEquals($user->getModified(), ['name' => 'jon']);
        $this->assertEquals($user->getModified(true), ['name' => 'jwagejon']);
        $user->save();
        $this->assertEquals($user->getModified(), []);
        $this->assertEquals($user->getModified(true), []);
        $this->assertEquals($user->getLastModified(), ['name' => 'jon']);
        $this->assertEquals($user->getLastModified(true), ['name' => 'jwagejon']);
    }
}
