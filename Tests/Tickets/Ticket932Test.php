<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket932Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'UserNoAutoIncrement';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }

        public function testInit()
        {
            static::$dbh  = new \Doctrine1\Adapter\Mock('pgsql');
            static::$conn = \Doctrine1\Manager::getInstance()->openConnection(static::$dbh);
            $this->assertEquals(\Doctrine1\Core::IDENTIFIER_NATURAL, static::$conn->getTable('UserNoAutoIncrement')->getIdentifierType());
        }

        public function testCreateNewUserNoAutoIncrement()
        {
            $newUser               = new \UserNoAutoIncrement();
            $newUser->id           = 1;
            $newUser->display_name = 'Mah Name';
            $newUser->save();
            $this->assertEquals(\Doctrine1\Record\State::CLEAN, $newUser->state());
            $this->assertEquals(1, $newUser->id);
        }
    }
}

namespace {
    class UserNoAutoIncrement extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => false, 'notnull' => true]);
            $this->hasColumn('display_name', 'string', 255, ['notnull' => true]);
        }
    }
}
