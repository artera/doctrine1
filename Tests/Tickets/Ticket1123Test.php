<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1123Test extends DoctrineUnitTestCase
    {
        public function testInit()
        {
            static::$dbh  = new \Doctrine1\Adapter\Mock('mysql');
            static::$conn = \Doctrine1\Manager::getInstance()->openConnection(static::$dbh);
        }

        public function testExportSql()
        {
            $sql = static::$conn->export->exportClassesSql(['Ticket_1123_User', 'Ticket_1123_UserReference']);
            $this->assertEquals(count($sql), 4);
            $this->assertEquals($sql[0], 'CREATE TABLE ticket_1123__user_reference (user1 BIGINT, user2 BIGINT, PRIMARY KEY(user1, user2)) ENGINE = INNODB');
            $this->assertEquals($sql[1], 'CREATE TABLE ticket_1123__user (id BIGINT AUTO_INCREMENT, name VARCHAR(30), PRIMARY KEY(id)) ENGINE = INNODB');
            $this->assertEquals($sql[2], 'ALTER TABLE ticket_1123__user_reference ADD CONSTRAINT ticket_1123__user_reference_user2_ticket_1123__user_id FOREIGN KEY (user2) REFERENCES ticket_1123__user(id) ON DELETE CASCADE');
            $this->assertEquals($sql[3], 'ALTER TABLE ticket_1123__user_reference ADD CONSTRAINT ticket_1123__user_reference_user1_ticket_1123__user_id FOREIGN KEY (user1) REFERENCES ticket_1123__user(id) ON DELETE CASCADE');
        }
    }
}

namespace {
    class Ticket_1123_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 30);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1123_User as Friend',
                ['local'    => 'user1',
                                                           'foreign'  => 'user2',
                                                           'refClass' => 'Ticket_1123_UserReference',
                'equal'    => true]
            );
        }
    }

    class Ticket_1123_UserReference extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user1', 'integer', null, ['primary' => true]);
            $this->hasColumn('user2', 'integer', null, ['primary' => true]);
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_1123_User as User1', ['local' => 'user1', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Ticket_1123_User as User2', ['local' => 'user2', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }
}
