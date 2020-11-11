<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1763Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1763_User';
            parent::prepareTables();
        }

        public function testTest()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
            $user  = new \Ticket_1763_User();
            $valid = $user->isValid();
            $this->assertFalse($valid);
            $string = $user->getErrorStackAsString();
            $this->_validateErrorString($string);

            $this->expectException(\Exception::class);
            try {
                $user->save();
                $this->assertTrue(false);
            } catch (Exception $e) {
                $this->_validateErrorString($e->getMessage());
            }

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
        }

        protected function _validateErrorString($string)
        {
            $this->assertNotFalse(strstr($string, 'Validation failed in class Ticket_1763_User'));
            $this->assertNotFalse(strstr($string, '3 fields had validation errors:'));
            $this->assertNotFalse(strstr($string, '* 1 validator failed on email_address (notnull)'));
            $this->assertNotFalse(strstr($string, '* 1 validator failed on username (notnull)'));
            $this->assertNotFalse(strstr($string, '* 1 validator failed on ip_address (notnull)'));
        }
    }
}

namespace {
    class Ticket_1763_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'email_address',
                'string',
                255,
                ['unique'  => true,
                                                               'notnull' => true,
                'email'   => true]
            );
            $this->hasColumn(
                'username',
                'string',
                255,
                ['unique'  => true,
                'notnull' => true]
            );
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('ip_address', 'string', 255, ['notnull' => true, 'ip' => true]);
        }
    }
}
