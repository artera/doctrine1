<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1419Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $user = \Doctrine1\Query::create()
            ->from('User u')
            ->leftJoin('u.Phonenumber p')
            ->where('u.id = ?', 4)
            ->fetchOne();
        $user->Phonenumber[0]->phonenumber = '6155139185';
        $user->save();
        $user->free();

        $user = \Doctrine1\Query::create()
            ->from('User u')
            ->leftJoin('u.Phonenumber p')
            ->where('u.id = ?', 4)
            ->fetchOne();
        $this->assertNotEmpty($user->Phonenumber->getLast()->id);
        $this->assertEquals($user->Phonenumber->getLast()->phonenumber, '6155139185');
    }
}
