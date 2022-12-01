<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1208Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1208_User';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $user           = new \Ticket_1208_User();
            $user->username = 'jwage';
            $user->password = 'changeme';
            $user->save();
        }

        public function testTest()
        {
            $q = \Doctrine1\Query::create()
            ->from('Ticket_1208_User u');
            $user = $q->fetchOne([], \Doctrine1\HydrationMode::Array);

            $this->assertTrue(isset($user['pre_hydrate']));
            $this->assertTrue(isset($user['post_hydrate']));
        }
    }
}

namespace {
    class Ticket_1208_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }

        public function preHydrate($event)
        {
            $data                = $event->data;
            $data['pre_hydrate'] = 'pre hydrate value';
            $event->data         = $data;
        }

        public function postHydrate($event)
        {
            $data                 = $event->data;
            $data['post_hydrate'] = 'post hydrate value';
            $event->data          = $data;
        }
    }
}
