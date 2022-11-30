<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC267Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine1\Query::create()
            ->select('u.*, COUNT(e.id) as num_emails')
            ->from('User u')
            ->leftJoin('u.Email e')
            ->groupBy('u.id');

        $res = $q->fetchArray();
        $this->assertEquals($res[0]['num_emails'], '1');

        $q = \Doctrine1\Query::create()
            ->select('u.*, e.*, e.address as my_address')
            ->from('User u')
            ->leftJoin('u.Email e');

        $res = $q->fetchArray();

        $this->assertEquals($res[0]['Email']['my_address'], 'zYne@example.com');
        $this->assertEquals($res[0]['my_address'], 'zYne@example.com');
        $this->assertEquals($res[1]['my_address'], 'arnold@example.com');
    }
}
