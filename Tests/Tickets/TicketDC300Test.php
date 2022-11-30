<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC300Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $g1         = new \Ticket_DC300_Group();
            $g1['name'] = 'group1';
            $g1->save();

            $g2         = new \Ticket_DC300_Group();
            $g2['name'] = 'group2';
            $g2->save();

            $g3         = new \Ticket_DC300_Group();
            $g3['name'] = 'group3';
            $g3->save();

            $u1         = new \Ticket_DC300_User();
            $u1['name'] = 'user1';
            $u1['Groups']->add($g1);
            $u1['Groups']->add($g2);
            $u1->save();
        }

        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC300_Group';
            static::$tables[] = 'Ticket_DC300_User';
            static::$tables[] = 'Ticket_DC300_UserGroup';
            parent::prepareTables();
        }

        public function testRefTableEntriesOnManyToManyRelationsWithSynchronizeWithArray()
        {
            $u1 = \Doctrine1\Core::getTable('Ticket_DC300_User')->find(1);

            // update the groups user (id 1) is linked to
            $u1->synchronizeWithArray(
                [
                'Groups' => [
                ['name' => 'group1 update'],
                ['name' => 'group2 update']
                ]
                ]
            );
            $u1->save();

            // update the user-objects with real data from database
            $u1->loadReference('Groups');

            // check wether the two database-entries in RefTable exists
            $this->assertEquals(count($u1->Groups), 2);
        }
    }
}

namespace {
    class Ticket_DC300_Group extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_DC300_User as Users',
                [
                'local'    => 'group_id',
                'foreign'  => 'user_id',
                'refClass' => 'Ticket_DC300_UserGroup'
                ]
            );
        }
    }

    class Ticket_DC300_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_DC300_Group as Groups',
                [
                'local'    => 'user_id',
                'foreign'  => 'group_id',
                'refClass' => 'Ticket_DC300_UserGroup'
                ]
            );
        }
    }

    class Ticket_DC300_UserGroup extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id', 'integer');
            $this->hasColumn('group_id', 'integer');
        }
    }
}
