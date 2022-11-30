<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1277Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['T1277_User'];

        public static function prepareData(): void
        {
            $user1           = new \T1277_User();
            $user1->username = 'User1';
            $user1->email    = null;
            $user1->save();

            $user2           = new \T1277_User();
            $user2->username = 'User2';
            $user2->email    = 'some@email';
            $user2->save();
        }

        /**
         * Tests that:
         * 1) a record in PROXY state is switched to CLEAN state when he is queried again with all props.
         */
        public function testTicket()
        {
            static::$conn->getTable('T1277_User')->clear(); // clear identity map

            $q = new \Doctrine1\Query();
            $u = $q->select('u.id')->from('T1277_User u')->where('u.id=1')->fetchOne();

            $this->assertEquals(1, $u->id);
            $this->assertEquals(\Doctrine1\Record\State::PROXY, $u->state());

            // In some other part of code I will query this table again and start making modifications to found records:
            $q     = new \Doctrine1\Query();
            $users = $q->select('u.*')->from('T1277_User u')->execute();

            $this->assertEquals(2, count($users));

            foreach ($users as $u) {
                $this->assertEquals(\Doctrine1\Record\State::CLEAN, $u->state());

                $u->username = 'new \username' . $u->id;
                $u->email    = 'some' . $u->id . '@email';

                $this->assertEquals('new \username' . $u->id, $u->username);
                $this->assertEquals('some' . $u->id . '@email', $u->email);
            }
        }

        /**
         * Tests that:
         * 1) a record in PROXY state is still in PROXY state when he is queries again but not with all props
         * 2) a record in PROXY state is switched to DIRTY state and all uninitialized props loaded
         *    if one of the uninitialized properties is accessed and some other (already initialized)
         *    properties have been modified before.
         */
        public function testTicket2()
        {
            static::$conn->getTable('T1277_User')->clear(); // clear identity map

            $q = new \Doctrine1\Query();
            $u = $q->select('u.id')->from('T1277_User u')->where('u.id=1')->fetchOne();

            $this->assertEquals(1, $u->id);
            $this->assertEquals(\Doctrine1\Record\State::PROXY, $u->state());

            // In some other part of code I will query this table again and start making modifications to found records:
            $q     = new \Doctrine1\Query();
            $users = $q->select('u.id, u.username')->from('T1277_User u')->execute();

            $this->assertEquals(2, count($users));

            foreach ($users as $u) {
                $this->assertEquals(\Doctrine1\Record\State::PROXY, $u->state());

                $u->username = 'new \username' . $u->id; // modify
                $u->email    = 'some' . $u->id . '@email'; // triggers load() to fill uninitialized props

                $this->assertEquals('new \username' . $u->id, $u->username);
                $this->assertEquals('some' . $u->id . '@email', $u->email);

                $this->assertEquals(\Doctrine1\Record\State::DIRTY, $u->state());
            }
        }

        /**
         * Tests that:
         * 1) a record in PROXY state is still in PROXY state when he is queries again but not with all props
         * 2) a record in PROXY state is switched to CLEAN state and all uninitialized props loaded
         *    if one of the uninitialized properties is accessed.
         */
        public function testTicket3()
        {
            static::$conn->getTable('T1277_User')->clear(); // clear identity map

            $q = new \Doctrine1\Query();
            $u = $q->select('u.id')->from('T1277_User u')->where('u.id=1')->fetchOne();

            $this->assertEquals(1, $u->id);
            $this->assertEquals(\Doctrine1\Record\State::PROXY, $u->state());

            // In some other part of code I will query this table again and start making modifications to found records:
            $q     = new \Doctrine1\Query();
            $users = $q->select('u.id, u.username')->from('T1277_User u')->execute();

            $this->assertEquals(2, count($users));

            foreach ($users as $u) {
                $this->assertEquals(\Doctrine1\Record\State::PROXY, $u->state());

                if ($u->id == 1) {
                    $this->assertEquals('User1', $u->username);
                    $u->email; // triggers load()
                } else {
                    $this->assertEquals('User2', $u->username);
                    $this->assertEquals('some@email', $u->email);
                }

                $this->assertEquals(\Doctrine1\Record\State::CLEAN, $u->state());
            }
        }
    }
}

namespace {
    class T1277_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('t1277_users');

            $this->hasColumns(
                [

                'id' => [
                    'type'          => 'integer',
                    'length'        => 4,
                    'notnull'       => true,
                    'autoincrement' => true,
                    'primary'       => true
                ],

                'username' => [
                    'type'   => 'string',
                    'length' => 50
                ],

                'email' => [
                    'type'    => 'string',
                    'length'  => 50,
                    'default' => null,
                ],
                ]
            );
        }
    }
}
