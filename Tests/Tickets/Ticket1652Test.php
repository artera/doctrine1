<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1652Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'Ticket_1652_User';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $user       = new \Ticket_1652_User();
            $user->id   = 1;
            $user->name = 'floriank';
            $user->save();
        }

        public function testValidate()
        {
            $doctrine = new \ReflectionClass(\Doctrine1\Core::class);
            if ($doctrine->hasConstant('VALIDATE_USER')) {
                \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_USER);
            } else {
                //I only want my overridden Record->validate()-methods for validation
                \Doctrine1\Manager::getInstance()->setAttribute(
                    \Doctrine1\Core::ATTR_VALIDATE,
                    \Doctrine1\Core::VALIDATE_ALL & ~\Doctrine1\Core::VALIDATE_LENGTHS & ~\Doctrine1\Core::VALIDATE_CONSTRAINTS & ~\Doctrine1\Core::VALIDATE_TYPES
                );
            }

            $user       = \Doctrine1\Core::getTable('Ticket_1652_User')->findOneById(1);
            $user->name = 'test';
            if ($user->isValid()) {
                try {
                    $user->save();
                } catch (\Doctrine1\Validator\Exception $dve) {
                    // ignore
                }
            }

            $user = \Doctrine1\Core::getTable('Ticket_1652_User')->findOneById(1);

            $this->assertNotEquals($user->name, 'test');
            //reset validation to default for further testcases
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1652_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 30);
        }

        protected function validate()
        {
            if ($this->name == 'test') {
                $this->getErrorStack()->add('badName', 'No testnames allowed!');
                return false;
            }
        }
    }
}
