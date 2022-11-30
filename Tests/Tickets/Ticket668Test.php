<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket668Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T668_User';
            parent::prepareTables();
        }


        public static function prepareData(): void
        {
        }


        public function testTicket()
        {
            $query = \Doctrine1\Query::create()
            ->select('u.id')
            ->from('T668_User u')
            ->where("u.name LIKE '%foo OR bar%'");
            $this->assertEquals("SELECT u.id FROM T668_User u WHERE u.name LIKE '%foo OR bar%'", $query->getDql());
            $this->assertEquals($query->getSqlQuery(), "SELECT t.id AS t__id FROM t668_user t WHERE (t.name LIKE '%foo OR bar%')");
        }
    }
}

namespace {
    class T668_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('t668_user');
            $this->hasColumn('id', 'integer', 3, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('name', 'string', 100);
        }
    }
}
