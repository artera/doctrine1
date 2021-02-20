<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC240Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC240_User';
            static::$tables[] = 'Ticket_DC240_Role';
            static::$tables[] = 'Ticket_DC240_UserRole';
            static::$tables[] = 'Ticket_DC240_RoleReference';
            parent::prepareTables();
        }

        public function testTest()
        {
            $q = \Doctrine_Query::create()
            ->from('Ticket_DC240_User u')
            ->leftJoin('u.Roles r')
            ->leftJoin('r.Parents p')
            ->orderBy('username ASC');

            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.username AS t__username, t.password AS t__password, t2.id AS t2__id, t2.name AS t2__name, t4.id AS t4__id, t4.name AS t4__name FROM ticket__d_c240__user t LEFT JOIN ticket__d_c240__user_role t3 ON (t.id = t3.id_user) LEFT JOIN ticket__d_c240__role t2 ON t2.id = t3.id_role LEFT JOIN ticket__d_c240__role_reference t5 ON (t2.id = t5.id_role_child) LEFT JOIN ticket__d_c240__role t4 ON t4.id = t5.id_role_parent ORDER BY t.username ASC, t3.position ASC, t5.position DESC');
        }
    }
}

namespace {
    class Ticket_DC240_User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 64, ['notnull' => true]);
            $this->hasColumn('password', 'string', 128, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->hasMany('Ticket_DC240_Role as Roles', ['local' => 'id_user', 'foreign' => 'id_role', 'refClass' => 'Ticket_DC240_UserRole', 'orderBy' => 'position ASC']);
        }
    }

    class Ticket_DC240_Role extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 64);
        }

        public function setUp(): void
        {
            $this->hasMany('Ticket_DC240_User as Users', ['local' => 'id_role', 'foreign' => 'id_user', 'refClass' => 'Ticket_DC240_UserRole', 'orderBy' => 'position ASC']);
            $this->hasMany('Ticket_DC240_Role as Parents', ['local' => 'id_role_child', 'foreign' => 'id_role_parent', 'refClass' => 'Ticket_DC240_RoleReference', 'orderBy' => 'position DESC']);
            $this->hasMany('Ticket_DC240_Role as Children', ['local' => 'id_role_parent', 'foreign' => 'id_role_child', 'refClass' => 'Ticket_DC240_RoleReference', 'orderBy' => 'position ASC']);
        }
    }

    class Ticket_DC240_UserRole extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id_user', 'integer', null, ['primary' => true]);
            $this->hasColumn('id_role', 'integer', null, ['primary' => true]);
            $this->hasColumn('position', 'integer', null, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_DC240_User as User', ['local' => 'id_user', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Ticket_DC240_Role as Role', ['local' => 'id_role', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }

    class Ticket_DC240_RoleReference extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id_role_parent', 'integer', null, ['primary' => true]);
            $this->hasColumn('id_role_child', 'integer', null, ['primary' => true]);
            $this->hasColumn('position', 'integer', null, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_DC240_Role as Parent', ['local' => 'id_role_parent', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Ticket_DC240_Role as Child', ['local' => 'id_role_child', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }
}
