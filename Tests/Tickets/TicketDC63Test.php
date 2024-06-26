<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC63Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC63_User';
            parent::prepareTables();
        }

        public function testTest()
        {
            $sql = \Doctrine1\Core::generateSqlFromArray(['Ticket_DC63_User']);
            $this->assertEquals($sql[0], 'CREATE TABLE ticket__d_c63__user (id INTEGER, email_address VARCHAR(255) UNIQUE, username VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255), PRIMARY KEY(id, username))');
        }
    }
}

namespace {
    class Ticket_DC63_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true]);
            $this->hasColumn('email_address', 'string', 255, ['unique' => false]);
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255, ['primary' => true]);

            $this->_table->column('username')->unique = true;
            $this->_table->column('username')->primary = true;
            $this->_table->column('username')->notnull = true;
            $this->_table->column('email_address')->unique = true;
            $this->_table->column('password')->primary = false;
        }
    }
}
