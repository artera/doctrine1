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
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE, true);
            try {
                $user           = new \Ticket_1658_User();
                $user->password = 'test';
                $this->assertTrue(false);
            } catch (\Doctrine_Exception $e) {
                $this->assertEquals($e->getMessage(), 'Set password called');
            }

            try {
                $user = new \Ticket_1658_User();
                $user->fromArray(['password' => 'test']);
                $this->assertTrue(false);
            } catch (\Doctrine_Exception $e) {
                $this->assertEquals($e->getMessage(), 'Set password called');
            }
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE, false);
        }
    }
}

namespace {
    class Ticket_1658_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }

        public function setPassword($password)
        {
            throw new \Doctrine_Exception('Set password called');
        }
    }
}
