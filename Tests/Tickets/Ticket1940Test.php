<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1940Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1940_User';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine1\Manager::getInstance()->setAutoAccessorOverride(true);

            $user = new \Ticket_1940_User();
            $user->fromArray(['username' => 'jwage', 'password' => 'changeme', 'email_address' => 'jonwage@gmail.com']);

            $userArray = $user->toArray();
            $this->assertEquals('jwage-modified', $userArray['username']);
            $this->assertEquals(md5('changeme'), $userArray['password']);
            $this->assertEquals('jonwage@gmail.com-modified-modified', $userArray['email_address']);

            \Doctrine1\Manager::getInstance()->setAutoAccessorOverride(false);
        }
    }
}

namespace {
    class Ticket_1940_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('email_address', 'string', 255);

            $this->hasMutator('password', 'customSetPassword');
            $this->hasAccessor('username', 'customGetUsername');
        }

        public function getEmailAddress()
        {
            return $this->get('email_address') . '-modified';
        }

        public function setEmailAddress($emailAddress)
        {
            $this->set('email_address', $emailAddress . '-modified');
        }

        public function customGetUsername()
        {
            return $this->get('username') . '-modified';
        }

        public function customSetPassword($value)
        {
            return $this->set('password', md5($value));
        }
    }
}
