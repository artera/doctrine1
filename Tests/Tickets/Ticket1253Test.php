<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1253Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1253_User';
            static::$tables[] = 'Ticket_1253_UserType';
            parent::prepareTables();
        }

        public function testTest()
        {
            $test2       = new \Ticket_1253_UserType();
            $test2->name = 'one';
            $test2->save();

            $test3       = new \Ticket_1253_UserType();
            $test3->name = 'two';
            $test3->save();

            $test            = new \Ticket_1253_User();
            $test->name      = 'test';
            $test->type_name = 'one';
            $test->save();

            $q = \Doctrine_Query::create()
            ->from('Ticket_1253_User u')
            ->leftJoin('u.Type');

            // This will never work because t.type_name is the emulated enum value and t2.name is the actual name
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.name AS t__name, t.type_name AS t__type_name, t2.id AS t2__id, t2.name AS t2__name FROM ticket_1253__user t LEFT JOIN ticket_1253__user_type t2 ON t.type_name = t2.name');
            $results = $q->fetchArray();
            $this->assertEquals($results[0]['Type']['name'], 'one');
        }
    }
}

namespace {
    class Ticket_1253_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string');
            $this->hasColumn('type_name', 'enum', 9, ['values' => ['one', 'two']]);
        }

        public function setUp()
        {
            $this->hasOne('Ticket_1253_UserType as Type', ['local' => 'type_name', 'foreign' => 'name']);
        }
    }

    class Ticket_1253_UserType extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string');
        }

        public function setUp()
        {
            $this->hasMany('Ticket_1253_User as User', ['local' => 'name', 'foreign' => 'type_name', 'owningSide' => true]);
        }
    }
}
