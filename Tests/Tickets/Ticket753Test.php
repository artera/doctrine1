<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket753Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $origOptions = static::$conn->getDefaultColumnOptions();
            static::$conn->setDefaultColumnOptions(['type' => 'string', 'length' => 255, 'notnull' => true]);

            $origIdOptions = static::$conn->getDefaultIdentifierOptions();
            static::$conn->setDefaultIdentifierOptions(['name' => '%s_id', 'length' => 25, 'type' => 'string', 'autoincrement' => false]);

            $userTable = \Doctrine1\Core::getTable('Ticket_753_User');

            $definition = $userTable->getDefinitionOf('username');
            $this->assertEquals($definition, ['type' => 'string', 'length' => 255, 'notnull' => true]);

            $definition = $userTable->getDefinitionOf('ticket_753__user_id');
            $this->assertEquals($definition, ['type' => 'string', 'length' => 25, 'autoincrement' => false, 'primary' => true, 'notnull' => true]);

            static::$conn->setDefaultColumnOptions($origOptions);
            static::$conn->setDefaultIdentifierOptions($origIdOptions);
        }
    }
}

namespace {
    class Ticket_753_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username');
            $this->hasColumn('password');
        }
    }
}
