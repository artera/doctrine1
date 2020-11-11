<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC238Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $conn = \Doctrine_Manager::connection('sqlite::memory:', 'test', false);
            $conn->export->exportClasses(['Ticket_DC238_User']);

            $user           = new \Ticket_DC238_User();
            $user->username = 'jwage';
            $user->password = 'changeme';
            $user->save();

            $profiler = new \Doctrine_Connection_Profiler();
            $conn->addListener($profiler);

            $cacheDriver = new \Doctrine_Cache_Array();
            $q           = \Doctrine::getTable('Ticket_DC238_User')
            ->createQuery('u')
            ->useResultCache($cacheDriver, 3600, 'user_query');

            $this->assertEquals(0, $profiler->count());

            $this->assertEquals(1, $q->count());

            $this->assertEquals(1, $profiler->count());

            $this->assertEquals(1, $q->count());

            $this->assertEquals(1, $profiler->count());

            $this->assertTrue($cacheDriver->contains('user_query_count'));
        }
    }
}

namespace {
    class Ticket_DC238_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }
    }
}
