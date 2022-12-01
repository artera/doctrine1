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
            $q = \Doctrine1\Core::getTable('Ticket_DC7_User')
            ->createQuery('u')
            ->setHydrationMode(\Doctrine1\HydrationMode::OnDemand);

            $results = $q->execute();
            $this->assertEquals('Doctrine1\Collection\OnDemand', $results::class);

            $count = 0;
            foreach ($results as $result) {
                $count++;
            }
            $this->assertEquals($count, 10);
        }
    }
}

namespace {
    class Ticket_DC7_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }
    }
}
