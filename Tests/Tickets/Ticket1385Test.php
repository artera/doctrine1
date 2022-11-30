<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1385Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1385_User1';
            static::$tables[] = 'Ticket_1385_User2';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);

            $user                = new \Ticket_1385_User1();
            $user->username      = 'jwage';
            $user->password      = 'changeme';
            $user->email_address = 'jonwage@ertoihertionerti.com';
            $this->assertTrue($user->isValid());

            $user                = new \Ticket_1385_User2();
            $user->username      = 'jwage';
            $user->password      = 'changeme';
            $user->email_address = 'jonwage@ertoihertionerti.com';
            $this->assertFalse($user->isValid());

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1385_User1 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('email_address', 'string', 255, ['email' => ['useMxCheck' => false]]);
        }
    }

    class Ticket_1385_User2 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('email_address', 'string', 255, ['email' => ['useMxCheck' => true]]);
        }
    }
}
