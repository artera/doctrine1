<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1380Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine1\Query::create()
            ->select('u.*, COUNT(p.id) as num_phonenumbers')
            ->from('User u')
            ->leftJoin('u.Phonenumber p');
        $users = $q->fetchArray();
        $this->assertTrue(isset($users[0]['num_phonenumbers']));
    }
}
