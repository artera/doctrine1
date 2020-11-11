<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1923Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1923_User';
            static::$tables[] = 'Ticket_1923_User2';

            parent::prepareTables();
        }

        public function testTest()
        {
            $sql = \Doctrine_Core::generateSqlFromArray(['Ticket_1923_User']);
            $this->assertEquals($sql[1], 'CREATE INDEX username_idx ON ticket_1923__user (login)');

            $sql = \Doctrine_Core::generateSqlFromArray(['Ticket_1923_User2']);
            $this->assertEquals($sql[1], 'CREATE INDEX username2_idx ON ticket_1923__user2 (login DESC)');
        }
    }
}

namespace {
    class Ticket_1923_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('login as username', 'string', 255);
            $this->hasColumn('password', 'string', 255);

            $this->index('username', ['fields' => ['username']]);
        }
    }

    class Ticket_1923_User2 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('login as username', 'string', 255);
            $this->hasColumn('password', 'string', 255);

            $this->index('username2', ['fields' => ['username' => ['sorting' => 'DESC']]]);
        }
    }
}
