<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC141Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine1\Core::getTable('User')
            ->createQuery('u')
            ->where('u.name LIKE :name OR u.email_id = :email_id', [':email_id' => 2, ':name' => '%zYne%']);
        $users = $q->fetchArray();
        $this->assertEquals(count($users), 2);
    }
}
