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
            $doctrine = new \ReflectionClass('Doctrine');
            if ($doctrine->hasConstant('VALIDATE_USER')) {
                \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_USER);
            } else {
                //I only want my overridden Record->validate()-methods for validation
                \Doctrine_Manager::getInstance()->setAttribute(
                    \Doctrine_Core::ATTR_VALIDATE,
                    \Doctrine_Core::VALIDATE_ALL & ~Doctrine_Core::VALIDATE_LENGTHS & ~Doctrine_Core::VALIDATE_CONSTRAINTS & ~Doctrine_Core::VALIDATE_TYPES
                );
            }

            $user       = \Doctrine_Core::getTable('Ticket_1652_User')->findOneById(1);
            $user->name = 'test';
            if ($user->isValid()) {
                try {
                    $user->save();
                } catch (Doctrine_Validator_Exception $dve) {
                    // ignore
                }
            }

            $user = \Doctrine_Core::getTable('Ticket_1652_User')->findOneById(1);

            $this->assertNotEquals($user->name, 'test');
            //reset validation to default for further testcases
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
        }
    }
}

namespace {
    class Ticket_1652_User extends Doctrine_Record
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
