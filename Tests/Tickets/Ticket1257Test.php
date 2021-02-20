<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1257Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1257_User';
            static::$tables[] = 'Ticket_1257_Role';
            parent::prepareTables();
        }

        public function testTicket()
        {
            $user                    = new \Ticket_1257_User();
            $user->username          = 'jwage';
            $user->Role->name        = 'Developer';
            $user->Role->description = 'Programmer/Developer';
            $user->save();

            $q = \Doctrine_Query::create()
            ->select('u.id, u.username')
            ->from('Ticket_1257_User u')
            ->leftJoin('u.Role r')
            ->addSelect('r.id, r.name, r.description');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.username AS t__username, t2.id AS t2__id, t2.name AS t2__name, t2.description AS t2__description FROM ticket_1257__user t LEFT JOIN ticket_1257__role t2 ON t.role_id = t2.id');
            $results = $q->fetchArray();
            $this->assertEquals($results[0]['Role']['name'], 'Developer');
        }
    }
}

namespace {
    class Ticket_1257_User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('role_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_1257_Role as Role', ['local' => 'role_id', 'foreign' => 'id']);
        }
    }

    class Ticket_1257_Role extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('description', 'string');
        }

        public function setUp(): void
        {
            $this->hasMany('Ticket_1257_User as Users', ['local' => 'id', 'foreign' => 'role_id']);
        }
    }
}
