<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1653Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'Ticket_1653_User';
            static::$tables[] = 'Ticket_1653_Email';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }

        public function testValidate()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);

            $user = new \Ticket_1653_User();
            $mail = new \Ticket_1653_Email();

            $user->id       = 1;
            $user->name     = 'floriank';
            $user->emails[] = $mail;

            //explicit call of isValid() should return false since $mail->address is null

            $this->assertFalse($user->isValid(true));

            //reset validation to default for further testcases
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_NONE);
        }

        public function testModified()
        {
            $user           = new \Ticket_1653_User();
            $mail           = new \Ticket_1653_Email();
            $mail->address  = 'test';
            $user->emails[] = $mail;

            // Should return true since one of its relationships is modified
            $this->assertTrue($user->isModified(true));

            $user = new \Ticket_1653_User();
            $this->assertFalse($user->isModified(true));
            $user->name = 'floriank';
            $this->assertTrue($user->isModified(true));
        }
    }
}

namespace {
    class Ticket_1653_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1653_Email as emails',
                ['local' => 'id',
                                                  'foreign'         => 'user_id',
                'cascade'         => ['delete']]
            );
        }

        protected function validate()
        {
            if ($this->name == 'test') {
                $this->getErrorStack()->add('badName', 'No testnames allowed!');
                return false;
            }
        }
    }

    class Ticket_1653_Email extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id', 'integer');
            $this->hasColumn('address', 'string', 255, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_1653_User as user',
                ['local' => 'user_id',
                                                  'foreign'     => 'id',
                'cascade'     => ['delete']]
            );
        }
    }
}
