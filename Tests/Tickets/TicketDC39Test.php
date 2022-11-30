<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC39Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $g1         = new \Ticket_DC39_Group();
            $g1['name'] = 'group1';
            $g1->save();

            $g2         = new \Ticket_DC39_Group();
            $g2['name'] = 'group2';
            $g2->save();

            $u1             = new \Ticket_DC39_User();
            $u1['group_id'] = 1;
            $u1['name']     = 'user1';
            $u1->save();

            $u2             = new \Ticket_DC39_User();
            $u2['group_id'] = 2;
            $u2['name']     = 'user2';
            $u2->save();
        }

        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC39_Group';
            static::$tables[] = 'Ticket_DC39_User';
            parent::prepareTables();
        }

        public function testOneToManyRelationsWithSynchronizeWithArray()
        {
            // link group (id 2) with users (id 1,2)
            $group = \Doctrine1\Core::getTable('Ticket_DC39_Group')->find(2);
            $group->synchronizeWithArray(
                [
                'Users' => [1, 2]
                ]
            );
            $group->save();

            // update the user-objects with real data from database
            $user1 = \Doctrine1\Core::getTable('Ticket_DC39_User')->find(1);
            $user2 = \Doctrine1\Core::getTable('Ticket_DC39_User')->find(2);

            // compare the group_id (should be 2) with the group_id set through $group->synchronizeWithArray
            $this->assertEquals($group->Users[0]->group_id, 2);
            $this->assertEquals($group->Users[1]->group_id, 2);
        }
    }
}

namespace {
    class Ticket_DC39_Group extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_DC39_User as Users',
                [
                'local'   => 'id',
                'foreign' => 'group_id'
                ]
            );
        }
    }

    class Ticket_DC39_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('group_id', 'integer');
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_DC39_Group as Group',
                [
                'local'   => 'group_id',
                'foreign' => 'id'
                ]
            );
        }
    }
}
