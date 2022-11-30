<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1865Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1865_User';
            static::$tables[] = 'Ticket_1865_Profile';
            parent::prepareTables();
        }

        public function testSaveWithRelated()
        {
            $user            = new \Ticket_1865_User();
            $user->name      = 'hello';
            $user->loginname = 'world';
            $user->password  = '!';
            $user->Profile;
            $user->save();

            $this->assertNotEquals($user->Profile->id, null); // Ticket_1865_Profile is saved
            $user->delete();
        }

        public function testSaveWithRelatedWithPreInsert()
        {
            $user            = new \Ticket_1865_User();
            $user->name      = 'hello';
            $user->loginname = 'world';
            $user->password  = '!';
            $user->save(); // $user->Ticket_1865_Profile must be called in \Ticket_1865_User::preInsert

            $this->assertNotEquals($user->Profile->id, null); // Ticket_1865_Profile is NOT saved - test failure
            $user->delete();
        }
    }
}

namespace {
    class Ticket_1865_Profile extends \Doctrine1\Record
    {
        public function setUp(): void
        {
            $this->hasOne('Ticket_1865_User as User', ['local' => 'id', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 20, ['autoincrement', 'primary']);
            $this->hasColumn('user_id', 'integer', 20, ['notnull', 'unique']);
            $this->hasColumn('icq', 'string', 9, ['notnull']);
        }
    }

    class Ticket_1865_User extends \Doctrine1\Record
    {
        public function setUp(): void
        {
            $this->hasOne('Ticket_1865_Profile as Profile', ['local' => 'id', 'foreign' => 'user_id']);
        }
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 20, ['autoincrement', 'primary']);
            $this->hasColumn('name', 'string', 50);
            $this->hasColumn('loginname', 'string', 20, ['unique']);
            $this->hasColumn('password', 'string', 16);
        }
        public function preInsert($event)
        {
            $this->Profile;
            $this->Profile->icq = '';
        }
    }
}
