<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket632Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_632_User';
            static::$tables[] = 'Ticket_632_Group';
            static::$tables[] = 'Ticket_632_UserGroup';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $user                 = new \Ticket_632_User();
            $user->username       = 'jwage';
            $user->password       = 'changeme';
            $user->Groups[]->name = 'Group One';
            $user->Groups[]->name = 'Group Two';
            $user->Groups[]->name = 'Group Three';
            $user->save();
        }

        public function testTest()
        {
            $user = \Doctrine_Query::create()
            ->from('Ticket_632_User u, u.Groups g')
            ->where('u.username = ?', 'jwage')
            ->limit(1)
            ->fetchOne();
            $this->assertEquals($user->Groups->count(), 3);
            unset($user->Groups[2]);
            $this->assertEquals($user->Groups->count(), 2);
            $user->save(); // This deletes the UserGroup association and the Group record

            // We should still have 3 groups
            $groups = \Doctrine_Core::getTable('Ticket_632_Group')->findAll();
            $this->assertEquals($groups->count(), 3);
        }
    }
}

namespace {
    class Ticket_632_User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_632_Group as Groups',
                ['local'    => 'user_id',
                                                           'foreign'  => 'group_id',
                'refClass' => 'Ticket_632_UserGroup']
            );
        }
    }

    class Ticket_632_Group extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_632_User as Users',
                ['local'    => 'group_id',
                                                         'foreign'  => 'user_id',
                'refClass' => 'Ticket_632_UserGroup']
            );
        }
    }

    class Ticket_632_UserGroup extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id', 'integer', 4, ['primary' => true]);
            $this->hasColumn('group_id', 'integer', 4, ['primary' => true]);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_632_User as User',
                ['local'   => 'user_id',
                'foreign' => 'id']
            );

            $this->hasOne(
                'Ticket_632_Group as Group',
                ['local'   => 'group_id',
                'foreign' => 'id']
            );
        }
    }
}
