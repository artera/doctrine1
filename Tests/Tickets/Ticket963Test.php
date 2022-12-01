<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket963Test extends DoctrineUnitTestCase
    {
        public function testInit()
        {
            static::$dbh  = new \Doctrine1\Adapter\Mock('mysql');
            static::$conn = \Doctrine1\Manager::getInstance()->openConnection(static::$dbh);
        }

        public function testExportSql()
        {
            $sql = static::$conn->export->exportClassesSql(['Ticket_963_User', 'Ticket_963_Email']);
            $this->assertEquals(count($sql), 3);
            $this->assertEquals($sql[0], 'CREATE TABLE ticket_963__user (id BIGINT AUTO_INCREMENT, username VARCHAR(255), password VARCHAR(255), PRIMARY KEY(id)) ENGINE = InnoDB');
            $this->assertEquals($sql[1], 'CREATE TABLE ticket_963__email (user_id INT, address2 VARCHAR(255), PRIMARY KEY(user_id)) ENGINE = InnoDB');
            $this->assertEquals($test = isset($sql[2]) ? $sql[2]:null, 'ALTER TABLE ticket_963__email ADD CONSTRAINT ticket_963__email_user_id_ticket_963__user_id FOREIGN KEY (user_id) REFERENCES ticket_963__user(id) ON DELETE CASCADE');
        }
    }
}

namespace {
    class Ticket_963_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_963_Email as Email',
                ['local' => 'id',
                'foreign'                       => 'user_id']
            );
        }
    }

    class Ticket_963_Email extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id', 'integer', 4, ['primary' => true]);
            $this->hasColumn('address2', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_963_User as User',
                [
                                'local'      => 'user_id',
                                'foreign'    => 'id',
                                'owningSide' => true,
                'onDelete'   => 'CASCADE']
            );
        }
    }
}
