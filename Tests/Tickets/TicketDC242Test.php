<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC242Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC242_User';
            static::$tables[] = 'Ticket_DC242_Role';
            static::$tables[] = 'Ticket_DC242_UserRole';
            static::$tables[] = 'Ticket_DC242_RoleReference';
            parent::prepareTables();
        }

        public function testTest()
        {
            $role       = new \Ticket_DC242_Role();
            $role->name = 'publisher';
            $role->save();

            $role       = new \Ticket_DC242_Role();
            $role->name = 'reviewer';
            $role->save();

            $role       = new \Ticket_DC242_Role();
            $role->name = 'mod';
            $role->save();

            $user = new \Ticket_DC242_User();
            $user->fromArray(
                [
                'username' => 'test',
                'password' => 'test',
                'Roles'    => [1, 2, 3],
                ]
            );
            $user->save();

            $user->fromArray(
                [
                'Roles' => [1, 3],
                ]
            );
            $user->save();
            $user->refresh(true);

            $this->assertEquals($user->Roles[0]->id, 1);
            $this->assertEquals($user->Roles[1]->id, 3);
        }
    }
}

namespace {
    class Ticket_DC242_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 64, ['notnull' => true]);
            $this->hasColumn('password', 'string', 128, ['notnull' => true]);
        }

        public function setUp()
        {
            $this->hasMany('Ticket_DC242_Role as Roles', ['local' => 'id_user', 'foreign' => 'id_role', 'refClass' => 'Ticket_DC242_UserRole']);
        }
    }

    class Ticket_DC242_Role extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 64);
        }

        public function setUp()
        {
            $this->hasMany('Ticket_DC242_User as Users', ['local' => 'id_role', 'foreign' => 'id_user', 'refClass' => 'Ticket_DC242_UserRole']);
            $this->hasMany('Ticket_DC242_Role as Parents', ['local' => 'id_role_child', 'foreign' => 'id_role_parent', 'refClass' => 'Ticket_DC242_RoleReference']);
            $this->hasMany('Ticket_DC242_Role as Children', ['local' => 'id_role_parent', 'foreign' => 'id_role_child', 'refClass' => 'Ticket_DC242_RoleReference']);
        }
    }

    class Ticket_DC242_UserRole extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('id_user', 'integer', null, ['primary' => true]);
            $this->hasColumn('id_role', 'integer', null, ['primary' => true]);
        }

        public function setUp()
        {
            $this->hasOne('Ticket_DC242_User as User', ['local' => 'id_user', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Ticket_DC242_Role as Role', ['local' => 'id_role', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }

    class Ticket_DC242_RoleReference extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('id_role_parent', 'integer', null, ['primary' => true]);
            $this->hasColumn('id_role_child', 'integer', null, ['primary' => true]);
        }

        public function setUp()
        {
            $this->hasOne('Ticket_DC242_Role as Parent', ['local' => 'id_role_parent', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Ticket_DC242_Role as Child', ['local' => 'id_role_child', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }
}
