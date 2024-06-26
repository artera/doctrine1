<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1280Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            //static::$tables = array();
            static::$tables[] = 'Ticket_1280_User';
            static::$tables[] = 'Ticket_1280_Group';
            parent::prepareTables();
        }

        public function testTicket()
        {
            $group       = new \Ticket_1280_Group();
            $group->name = 'Core Dev';
            $group->save();

            $user        = new \Ticket_1280_User();
            $user->Group = $group;
            $user->name  = 'jwage';
            $user->save();

            $this->assertEquals($user->group_id, $group->id);

            $user->Group = null;
            $user->save();

            $this->assertEquals($user->group_id, null);
        }
    }
}

namespace {
    class Ticket_1280_User extends \Doctrine1\Record
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
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_1280_Group as Group',
                [
                'local'   => 'group_id',
                'foreign' => 'id'
                ]
            );
        }
    }


    class Ticket_1280_Group extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1280_User as Users',
                [
                'local'   => 'id',
                'foreign' => 'group_id'
                ]
            );
        }
    }
}
