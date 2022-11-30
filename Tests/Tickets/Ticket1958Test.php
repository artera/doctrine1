<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1958Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1958_User';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);

            $user           = new \Ticket_1958_User();
            $user->username = 'jwage';
            $user->password = 'test';
            $user->save();

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1958_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('foo', 'integer', 4, ['notnull' => true, 'default' => '0', 'unsigned' => 1]);
        }
    }
}
