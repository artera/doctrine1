<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1131Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            //static::$tables = array();
            static::$tables[] = 'Ticket_1131_User';
            static::$tables[] = 'Ticket_1131_Group';
            static::$tables[] = 'Ticket_1131_Role';
            parent::prepareTables();
        }


        public static function prepareData(): void
        {
            parent::prepareData();

            $role       = new \Ticket_1131_Role();
            $role->name = 'Role One';
            $role->save();
            $role_one = $role->id;
            $role->free();

            $role       = new \Ticket_1131_Role();
            $role->name = 'Role Two';
            $role->save();
            $role_two = $role->id;
            $role->free();

            $group          = new \Ticket_1131_Group();
            $group->role_id = $role_one;
            $group->name    = 'Core Dev';
            $group->save();

            $user          = new \Ticket_1131_User();
            $user->Group   = $group;
            $user->role_id = $role_two;
            $user->name    = 'jwage';
            $user->save();

            $group->free();
            $user->free();
        }

        public function testTicket()
        {
            $user = \Doctrine1\Query::create()
            ->from('Ticket_1131_User u')
            ->where('u.id = ?')->fetchOne([1]);

            $this->assertEquals($user->Group->id, 1);
            $this->assertFalse($user->get('group_id') instanceof \Doctrine1\Record);
        }

        public function testTicketWithOverloadingAndTwoQueries()
        {
            $orig = \Doctrine1\Manager::getInstance()->getAttribute(\Doctrine1\Core::ATTR_AUTO_ACCESSOR_OVERRIDE);
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_AUTO_ACCESSOR_OVERRIDE, true);

            $user = \Doctrine1\Query::create()
            ->from('Ticket_1131_User u')
            ->where('u.id = ?')->fetchOne([1]);

            $user = \Doctrine1\Query::create()
            ->from('Ticket_1131_UserWithOverloading u')
            ->leftJoin('u.Group g')
            ->leftJoin('u.Role r')
            ->addWhere('u.id = ?')->fetchOne([1]);

            $this->assertEquals($user->Role->id, 1);
            $this->assertFalse($user->role_id instanceof \Doctrine1\Record);

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_AUTO_ACCESSOR_OVERRIDE, $orig);
        }
    }
}

namespace {
    class Ticket_1131_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'group_id',
                'integer',
                20,
                [
                'notnull' => false, 'default' => null
                ]
            );
            $this->hasColumn(
                'role_id',
                'integer',
                20,
                [
                'notnull' => false, 'default' => null
                ]
            );
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_1131_Group as Group',
                [
                'local'   => 'group_id',
                'foreign' => 'id'
                ]
            );

            $this->hasOne(
                'Ticket_1131_Role as Role',
                [
                'local'   => 'role_id',
                'foreign' => 'id']
            );
        }
    }

    class Ticket_1131_UserWithOverloading extends Ticket_1131_User
    {
        public function getRole()
        {
            return $this->Group->Role;
        }

        public function getRoleId()
        {
            return $this->Group->role_id;
        }
    }

    class Ticket_1131_Group extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'role_id',
                'integer',
                20,
                [
                'notnull' => false, 'default' => null
                ]
            );
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_1131_Role as Role',
                [
                'local'   => 'role_id',
                'foreign' => 'id']
            );

            $this->hasMany(
                'Ticket_1131_User as Users',
                [
                'local'   => 'id',
                'foreign' => 'group_id'
                ]
            );
        }
    }

    class Ticket_1131_Role extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1131_User as Users',
                [
                'local'   => 'id',
                'foreign' => 'role_id'
                ]
            );
            $this->hasMany(
                'Ticket_1131_Group as Groups',
                [
                'local'   => 'id',
                'foreign' => 'role_id'
                ]
            );
        }
    }
}
