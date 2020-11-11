<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC7Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC7_User';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            for ($i = 0; $i < 10; $i++) {
                $user           = new \Ticket_DC7_User();
                $user->username = $i;
                $user->password = $i;
                $user->save();
            }
        }

        public function testOnDemandHydration()
        {
            $q = \Doctrine_Core::getTable('Ticket_DC7_User')
            ->createQuery('u')
            ->setHydrationMode(\Doctrine_Core::HYDRATE_ON_DEMAND);

            $results = $q->execute();
            $this->assertEquals(get_class($results), 'Doctrine_Collection_OnDemand');

            $count = 0;
            foreach ($results as $result) {
                $count++;
            }
            $this->assertEquals($count, 10);
        }
    }
}

namespace {
    class Ticket_DC7_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }
    }
}
