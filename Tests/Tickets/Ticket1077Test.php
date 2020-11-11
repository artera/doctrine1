<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1077Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1077_User';
            static::$tables[] = 'Ticket_1077_Phonenumber';
            parent::prepareTables();
        }

        public function testAutomaticAccessorsAndMutators()
        {
            $orig = \Doctrine_Manager::getInstance()->getAttribute(\Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE);
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE, true);
            $user           = new \Ticket_1077_User();
            $user->username = 'jwage';
            $user->password = 'changeme';
            $user->save();
            $this->assertEquals($user->getPassword(), '4cb9c8a8048fd02294477fcb1a41191a');
            $this->assertEquals($user->getUsername(), 'Username: jwage');
            $this->assertEquals($user->username, $user->getUsername());

            $numbers            = new \Doctrine_Collection('Phonenumber');
            $user->Phonenumbers = $numbers;

            $this->assertSame($user->phonenumbersTest, $numbers);

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE, $orig);
        }

        public function testDefiningCustomAccessorsAndMutators()
        {
            $user           = new \Ticket_1077_User();
            $user->username = 'jwage';
            $user->password = 'changeme';
            $user->hasAccessor('username', 'usernameAccessor');
            $user->hasMutator('username', 'usernameMutator');
            $username = 'test';
            $user->usernameMutator($username);
            $this->assertEquals($user->usernameAccessor(), $user->username);
            $this->assertEquals($user->usernameAccessor(), $username);
        }
    }
}

namespace {
    class Ticket_1077_User extends Doctrine_Record
    {
        public $phonenumbersTest = null;

        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }

        public function setUp()
        {
            $this->hasMany(
                'Ticket_1077_Phonenumber as Phonenumbers',
                ['local'   => 'id',
                'foreign' => 'user_id']
            );
        }

        public function usernameAccessor()
        {
            return $this->_get('username');
        }

        public function usernameMutator($value)
        {
            $this->_set('username', $value);
        }

        public function getPhonenumbers()
        {
            throw new \Exception('Testing that getPhonenumbers() is invoked');
        }

        public function setPhonenumbers($phonenumbers)
        {
            $this->phonenumbersTest = $phonenumbers;
            return $this->_set('Phonenumbers', $phonenumbers);
        }

        public function getUsername($load = true)
        {
            return 'Username: ' . $this->_get('username', $load);
        }

        public function setPassword($password)
        {
            return $this->_set('password', md5($password));
        }

        public function getPassword($load = true)
        {
            return $this->_get('password', $load);
        }
    }

    class Ticket_1077_Phonenumber extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('phonenumber', 'string', 55);
            $this->hasColumn('user_id', 'integer');
        }

        public function setUp()
        {
            $this->hasOne(
                'Ticket_1077_User as User',
                ['local'   => 'user_id',
                'foreign' => 'id']
            );
        }
    }
}
