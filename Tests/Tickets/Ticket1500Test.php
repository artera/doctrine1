<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1500Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'T1500_User';
            static::$tables[] = 'T1500_Group';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $group       = new \T1500_Group();
            $group->name = 'admin';
            $group->save();

            $user          = new \T1500_User();
            $user->groupId = $group->id;
            $user->name    = 'jwage';
            $user->save();

            $user          = new \T1500_User();
            $user->groupId = $group->id;
            $user->name    = 'guilhermeblanco';
            $user->save();
        }

        public function testTicket()
        {
            $q = \Doctrine_Query::create()
            ->from('T1500_User u')->innerJoin('u.Group g')->where('u.id = 1');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.user_id AS t__user_id, t.group_id AS t__group_id, t.name AS t__name, t2.group_id AS t2__group_id, t2.name AS t2__name FROM t1500__user t INNER JOIN t1500__group t2 ON t.group_id = t2.group_id WHERE (t.user_id = 1)');

            $q = \Doctrine_Query::create()
            ->from('T1500_Group g')->innerJoin('g.Users u')->where('g.id = 1');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.group_id AS t__group_id, t.name AS t__name, t2.user_id AS t2__user_id, t2.group_id AS t2__group_id, t2.name AS t2__name FROM t1500__group t INNER JOIN t1500__user t2 ON t.group_id = t2.group_id WHERE (t.group_id = 1)');
        }
    }
}

namespace {
    class T1500_User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id as id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('group_id as groupId', 'integer', null);
            $this->hasColumn('name', 'string', 100);
        }

        public function setUp(): void
        {
            $this->hasOne('T1500_Group as Group', ['local' => 'groupId', 'foreign' => 'id']);
        }
    }

    class T1500_Group extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('group_id as id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 100);
        }

        public function setUp(): void
        {
            $this->hasMany('T1500_User as Users', ['local' => 'id', 'foreign' => 'groupId']);
        }
    }
}
