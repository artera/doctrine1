<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1276Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS, true);
        $q = \Doctrine_Query::create()
            ->from('User u');
        $users = $q->fetchArray();
        $this->assertTrue(is_array($users));
        \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS, false);
    }
}
