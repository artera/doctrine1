<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1276Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_AUTO_FREE_QUERY_OBJECTS, true);
        $q = \Doctrine1\Query::create()
            ->from('User u');
        $users = $q->fetchArray();
        $this->assertTrue(is_array($users));
        \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_AUTO_FREE_QUERY_OBJECTS, false);
    }
}
