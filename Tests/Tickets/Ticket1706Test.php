<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1706Test extends DoctrineUnitTestCase
    {
        public function testCachedResultsAreSpecificToDsn()
        {
            $cacheDriver = new \Doctrine1\Cache\PHPArray();

            $conn1 = \Doctrine1\Manager::connection('sqlite::memory:', 'conn_1');
            $conn1->setResultCache($cacheDriver);

            $conn2 = \Doctrine1\Manager::connection('sqlite::memory:', 'conn_2');
            $conn2->setResultCache($cacheDriver);
            $this->assertNotEquals($conn1, $conn2);

            $manager = \Doctrine1\Manager::getInstance();
            $manager->setCurrentConnection('conn_1');
            $this->assertEquals($conn1, \Doctrine1\Manager::connection());

            \Doctrine1\Core::createTablesFromArray(['Ticket_1706_User']);

            $user       = new \Ticket_1706_User();
            $user->name = 'Allen';
            $user->save();

            $manager->setCurrentConnection('conn_2');
            $this->assertEquals($conn2, \Doctrine1\Manager::connection());

            \Doctrine1\Core::createTablesFromArray(['Ticket_1706_User']);

            $user       = new \Ticket_1706_User();
            $user->name = 'Bob';
            $user->save();

            $manager->setCurrentConnection('conn_1');
            $u1 = \Doctrine1\Query::create()
            ->from('Ticket_1706_User u')
            ->useResultCache()
            ->execute();

            $this->assertEquals(1, count($u1));
            $this->assertEquals('Allen', $u1[0]->name);

            $manager->setCurrentConnection('conn_2');
            $u2 = \Doctrine1\Query::create()
            ->from('Ticket_1706_User u')
            ->useResultCache()
            ->execute();

            $this->assertEquals(1, count($u2));
            $this->assertEquals('Bob', $u2[0]->name);
        }
    }
}

namespace {
    class Ticket_1706_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string');
            $this->hasColumn('password', 'string');
        }
    }
}
