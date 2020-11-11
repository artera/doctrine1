<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1341Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1341_User';
            static::$tables[] = 'Ticket_1341_Profile';
            parent::prepareTables();
        }

        public function testTest()
        {
            $user                = new \Ticket_1341_User();
                $user->username      = 'jwage';
                $user->password      = 'changeme';
                $user->Profile->name = 'Jonathan H. Wage';
                $user->save();

                $this->assertEquals(
                    $user->toArray(true),
                    [
                    'id'       => '1',
                    'username' => 'jwage',
                    'password' => 'changeme',
                    'Profile'  => [
                    'id'      => '1',
                    'name'    => 'Jonathan H. Wage',
                    'user_id' => '1',
                    ],
                    ]
                );
                $q = \Doctrine_Query::create()
                    ->from('Ticket_1341_User u')
                    ->leftJoin('u.Profile p');
                $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.username AS t__username, t.password AS t__password, t2.id AS t2__id, t2.name AS t2__name, t2.userid AS t2__userid FROM ticket_1341__user t LEFT JOIN ticket_1341__profile t2 ON t.id = t2.userid');
        }
    }
}

namespace {
    class Ticket_1341_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }

        public function setUp()
        {
            $this->hasOne('Ticket_1341_Profile as Profile', ['local' => 'id', 'foreign' => 'user_id']);
        }
    }

    class Ticket_1341_Profile extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('userId as user_id', 'integer');
        }

        public function setUp()
        {
            $this->hasOne('Ticket_1341_User as User', ['local' => 'user_id', 'foreign' => 'id']);
        }
    }
}
