<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1522Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $user = new \Ticket_1522_User();
            $user->fromArray(['username' => 'jwage', 'encrypted_password' => 'changeme']);
            $this->assertEquals($user->toArray(), ['id' => null, 'username' => 'jwage', 'password' => md5('changeme'), 'use_encrypted_password' => true]);
        }
    }
}

namespace {
    class Ticket_1522_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('use_encrypted_password', 'boolean');
        }

        public function setEncryptedPassword($value)
        {
            $this->use_encrypted_password = true;
            return $this->set('password', md5($value));
        }
    }
}
