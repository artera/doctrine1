<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1706Test extends DoctrineUnitTestCase
    {
        public function testCachedResultsAreSpecificToDsn()
        {
            $cacheDriver = new \Doctrine_Cache_Array();

            $conn1 = \Doctrine_Manager::connection('sqlite::memory:', 'conn_1');
            $conn1->setAttribute(\Doctrine_Core::ATTR_RESULT_CACHE, $cacheDriver);

            $conn2 = \Doctrine_Manager::connection('sqlite::memory:', 'conn_2');
            $conn2->setAttribute(\Doctrine_Core::ATTR_RESULT_CACHE, $cacheDriver);
            $this->assertNotEquals($conn1, $conn2);

            $manager = \Doctrine_Manager::getInstance();
            $manager->setCurrentConnection('conn_1');
            $this->assertEquals($conn1, \Doctrine_Manager::connection());

            \Doctrine_Core::createTablesFromArray(['Ticket_1706_User']);

            $user       = new \Ticket_1706_User();
            $user->name = 'Allen';
            $user->save();

            $manager->setCurrentConnection('conn_2');
            $this->assertEquals($conn2, \Doctrine_Manager::connection());

            \Doctrine_Core::createTablesFromArray(['Ticket_1706_User']);

            $user       = new \Ticket_1706_User();
            $user->name = 'Bob';
            $user->save();

            $manager->setCurrentConnection('conn_1');
            $u1 = \Doctrine_Query::create()
            ->from('Ticket_1706_User u')
            ->useResultCache()
            ->execute();

            $this->assertEquals(1, count($u1));
            $this->assertEquals('Allen', $u1[0]->name);

            $manager->setCurrentConnection('conn_2');
            $u2 = \Doctrine_Query::create()
            ->from('Ticket_1706_User u')
            ->useResultCache()
            ->execute();

            $this->assertEquals(1, count($u2));
            $this->assertEquals('Bob', $u2[0]->name);
        }
    }
}

namespace {
    class Ticket_1706_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string');
            $this->hasColumn('password', 'string');
        }
    }
}
