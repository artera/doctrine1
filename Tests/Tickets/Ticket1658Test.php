<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1658Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1658_User';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_AUTO_ACCESSOR_OVERRIDE, true);
            try {
                $user           = new \Ticket_1658_User();
                $user->password = 'test';
                $this->assertTrue(false);
            } catch (\Doctrine1\Exception $e) {
                $this->assertEquals($e->getMessage(), 'Set password called');
            }

            try {
                $user = new \Ticket_1658_User();
                $user->fromArray(['password' => 'test']);
                $this->assertTrue(false);
            } catch (\Doctrine1\Exception $e) {
                $this->assertEquals($e->getMessage(), 'Set password called');
            }
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_AUTO_ACCESSOR_OVERRIDE, false);
        }
    }
}

namespace {
    class Ticket_1658_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }

        public function setPassword($password)
        {
            throw new \Doctrine1\Exception('Set password called');
        }
    }
}
