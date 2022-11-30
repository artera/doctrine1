<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC302Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC302_Role';
            static::$tables[] = 'Ticket_DC302_RoleReference';
            static::$tables[] = 'Ticket_DC302_User';
            static::$tables[] = 'Ticket_DC302_UserRole';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $role1       = new \Ticket_DC302_Role();
            $role1->name = 'admin'; // id: 1
            $role1->save();

            $role2       = new \Ticket_DC302_Role();
            $role2->name = 'publisher'; // id: 2
            $role2->save();

            $role3       = new \Ticket_DC302_Role();
            $role3->name = 'reviewer'; // id: 3
            $role3->save();

            $role4       = new \Ticket_DC302_Role();
            $role4->name = 'mod'; // id: 4
            $role4->save();

            // reviewer inherits from admin, mod, publisher - in that order
            $role3->Parents[] = $role1;
            $role3->Parents[] = $role4;
            $role3->Parents[] = $role2;
            $role3->save();

            // update positions
            $query = \Doctrine1\Query::create()
            ->update('Ticket_DC302_RoleReference')
            ->set('position', '?', 0)
            ->where('id_role_child = ?', 3)
            ->andWhere('id_role_parent = ?', 1)
            ->execute();
            $query = \Doctrine1\Query::create()
            ->update('Ticket_DC302_RoleReference')
            ->set('position', '?', 1)
            ->where('id_role_child = ?', 3)
            ->andWhere('id_role_parent = ?', 4)
            ->execute();
            $query = \Doctrine1\Query::create()
            ->update('Ticket_DC302_RoleReference')
            ->set('position', '?', 2)
            ->where('id_role_child = ?', 3)
            ->andWhere('id_role_parent = ?', 2)
            ->execute();


            $user           = new \Ticket_DC302_User();
            $user->username = 'test';
            $user->password = 'test';
            $user->fromArray(['Roles' => [4, 2]]);
            $user->save();
            // update positions
            $query = \Doctrine1\Query::create()
            ->update('Ticket_DC302_UserRole')
            ->set('position', '?', 0)
            ->where('id_user = ?', 1)
            ->andWhere('id_role = ?', 4)
            ->execute();
            $query = \Doctrine1\Query::create()
            ->update('Ticket_DC302_UserRole')
            ->set('position', '?', 1)
            ->where('id_user = ?', 1)
            ->andWhere('id_role = ?', 2)
            ->execute();
        }

        public function testTest()
        {
            $profiler = new \Doctrine1\Connection\Profiler();
            static::$conn->addListener($profiler);

            $role    = \Doctrine1\Core::getTable('Ticket_DC302_Role')->find(3);
            $parents = $role->Parents->toArray();

            $this->assertEquals($parents[1]['Ticket_DC302_RoleReference'][0]['position'], 1);

            $events = $profiler->getAll();
            $event  = array_pop($events);
            $this->assertEquals($event->getQuery(), 'SELECT ticket__d_c302__role.id AS ticket__d_c302__role__id, ticket__d_c302__role.name AS ticket__d_c302__role__name, ticket__d_c302__role_reference.id_role_parent AS ticket__d_c302__role_reference__id_role_parent, ticket__d_c302__role_reference.id_role_child AS ticket__d_c302__role_reference__id_role_child, ticket__d_c302__role_reference.position AS ticket__d_c302__role_reference__position FROM ticket__d_c302__role INNER JOIN ticket__d_c302__role_reference ON ticket__d_c302__role.id = ticket__d_c302__role_reference.id_role_parent WHERE ticket__d_c302__role.id IN (SELECT id_role_parent FROM ticket__d_c302__role_reference WHERE id_role_child = ?) ORDER BY position');
        }
    }
}

namespace {
    class Ticket_DC302_Role extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 64);
        }

        public function setUp(): void
        {
            $this->hasMany('Ticket_DC302_User as Users', ['local' => 'id_role', 'foreign' => 'id_user', 'refClass' => 'Ticket_DC302_UserRole']);
            $this->hasMany('Ticket_DC302_Role as Parents', ['local' => 'id_role_child', 'foreign' => 'id_role_parent', 'refClass' => 'Ticket_DC302_RoleReference', 'orderBy' => 'position']);
            $this->hasMany('Ticket_DC302_Role as Children', ['local' => 'id_role_parent', 'foreign' => 'id_role_child', 'refClass' => 'Ticket_DC302_RoleReference']);
        }
    }

    class Ticket_DC302_RoleReference extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id_role_parent', 'integer', null, ['primary' => true]);
            $this->hasColumn('id_role_child', 'integer', null, ['primary' => true]);
            $this->hasColumn('position', 'integer', null, ['notnull' => true, 'default' => 0]);
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_DC302_Role as Parent', ['local' => 'id_role_parent', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Ticket_DC302_Role as Child', ['local' => 'id_role_child', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }

    class Ticket_DC302_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 64, ['notnull' => true]);
            $this->hasColumn('password', 'string', 128, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->hasMany('Ticket_DC302_Role as Roles', ['local' => 'id_user', 'foreign' => 'id_role', 'refClass' => 'Ticket_DC302_UserRole', 'orderBy' => 'position']);
        }
    }

    class Ticket_DC302_UserRole extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id_user', 'integer', null, ['primary' => true]);
            $this->hasColumn('id_role', 'integer', null, ['primary' => true]);
            $this->hasColumn('position', 'integer', null, ['notnull' => true, 'default' => 0]);
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_DC302_User as User', ['local' => 'id_user', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Ticket_DC302_Role as Role', ['local' => 'id_role', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }
}
