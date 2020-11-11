<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1764Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1764_User';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

            $user           = new \Ticket_1764_User();
            $user->username = 'jwage';
            $user->password = 'changeme';
            $user->rate     = 1;
            $this->assertEquals($user->isValid(), true);

            $sql = \Doctrine_Core::generateSqlFromArray(['Ticket_1764_User']);
            $this->assertEquals($sql[0], 'CREATE TABLE ticket_1764__user (id INTEGER PRIMARY KEY AUTOINCREMENT, username VARCHAR(255), password VARCHAR(255), rate DECIMAL(18,2) NOT NULL)');

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1764_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('rate', 'decimal', null, ['notnull' => true]);
        }
    }
}
