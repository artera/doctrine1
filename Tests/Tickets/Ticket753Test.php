<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket753Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $origOptions = static::$conn->getAttribute(\Doctrine1\Core::ATTR_DEFAULT_COLUMN_OPTIONS);
            static::$conn->setAttribute(\Doctrine1\Core::ATTR_DEFAULT_COLUMN_OPTIONS, ['type' => 'string', 'length' => 255, 'notnull' => true]);

            $origIdOptions = static::$conn->getAttribute(\Doctrine1\Core::ATTR_DEFAULT_IDENTIFIER_OPTIONS);
            static::$conn->setAttribute(\Doctrine1\Core::ATTR_DEFAULT_IDENTIFIER_OPTIONS, ['name' => '%s_id', 'length' => 25, 'type' => 'string', 'autoincrement' => false]);

            $userTable = \Doctrine1\Core::getTable('Ticket_753_User');

            $definition = $userTable->getDefinitionOf('username');
            $this->assertEquals($definition, ['type' => 'string', 'length' => 255, 'notnull' => true]);

            $definition = $userTable->getDefinitionOf('ticket_753__user_id');
            $this->assertEquals($definition, ['type' => 'string', 'length' => 25, 'autoincrement' => false, 'primary' => true, 'notnull' => true]);

            static::$conn->setAttribute(\Doctrine1\Core::ATTR_DEFAULT_COLUMN_OPTIONS, $origOptions);
            static::$conn->setAttribute(\Doctrine1\Core::ATTR_DEFAULT_IDENTIFIER_OPTIONS, $origIdOptions);
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
