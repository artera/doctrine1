<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1483Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $q = \Doctrine1\Query::create()
            ->from('Ticket_1483_User u')
            ->leftJoin('u.Groups g WITH g.id = (SELECT g2.id FROM Ticket_1483_Group g2 WHERE (g2.name = \'Test\' OR g2.name = \'Test2\'))');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.username AS t__username, t2.id AS t2__id, t2.name AS t2__name FROM ticket_1483__user t LEFT JOIN ticket_1483__user_group t3 ON (t.id = t3.user_id) LEFT JOIN ticket_1483__group t2 ON t2.id = t3.group_id AND (t2.id = (SELECT t4.id AS t4__id FROM ticket_1483__group t4 WHERE (t4.name = \'Test\' OR t4.name = \'Test2\')))');
        }

        public function testTest2()
        {
            $q = \Doctrine1\Query::create()
            ->from('Ticket_1483_User u')
            ->leftJoin('u.Groups g WITH g.id = (SELECT g2.id FROM Ticket_1483_Group g2 WHERE (g2.name = \'Test\' OR (g2.name = \'Test2\')))');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.username AS t__username, t2.id AS t2__id, t2.name AS t2__name FROM ticket_1483__user t LEFT JOIN ticket_1483__user_group t3 ON (t.id = t3.user_id) LEFT JOIN ticket_1483__group t2 ON t2.id = t3.group_id AND (t2.id = (SELECT t4.id AS t4__id FROM ticket_1483__group t4 WHERE (t4.name = \'Test\' OR t4.name = \'Test2\')))');
        }

        public function testTest3()
        {
            $q = \Doctrine1\Query::create()
            ->from('Ticket_1483_User u')
            ->leftJoin('u.Groups g WITH g.id = (SELECT g2.id FROM Ticket_1483_Group g2 WHERE ((g2.name = \'Test\' AND g2.name = \'Test2\') OR (g2.name = \'Test2\')))');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.username AS t__username, t2.id AS t2__id, t2.name AS t2__name FROM ticket_1483__user t LEFT JOIN ticket_1483__user_group t3 ON (t.id = t3.user_id) LEFT JOIN ticket_1483__group t2 ON t2.id = t3.group_id AND (t2.id = (SELECT t4.id AS t4__id FROM ticket_1483__group t4 WHERE ((t4.name = \'Test\' AND t4.name = \'Test2\') OR t4.name = \'Test2\')))');
        }

        public function testTest4()
        {
            $q = \Doctrine1\Query::create()
            ->from('Ticket_1483_User u')
            ->leftJoin('u.Groups g WITH g.id = (SELECT COUNT(*) FROM Ticket_1483_Group)');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.username AS t__username, t2.id AS t2__id, t2.name AS t2__name FROM ticket_1483__user t LEFT JOIN ticket_1483__user_group t3 ON (t.id = t3.user_id) LEFT JOIN ticket_1483__group t2 ON t2.id = t3.group_id AND (t2.id = (SELECT COUNT(*) AS t4__0 FROM ticket_1483__group t4))');
        }
    }
}

namespace {
    class Ticket_1483_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1483_Group as Groups',
                ['local'    => 'user_id',
                                                            'foreign'  => 'group_id',
                'refClass' => 'Ticket_1483_UserGroup']
            );
        }
    }

    class Ticket_1483_Group extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1483_User as Users',
                ['local'    => 'group_id',
                                                          'foreign'  => 'user_id',
                'refClass' => 'Ticket_1483_UserGroup']
            );
        }
    }


    class Ticket_1483_UserGroup extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id', 'integer');
            $this->hasColumn('group_id', 'integer');
        }
    }
}
