<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket255Test extends DoctrineUnitTestCase
    {
        protected static array $tables = [\Ticket_255_User::class];

        public function testTest()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, true);
            $user                = new \Ticket_255_User();
            $user->username      = 'jwage';
            $user->email_address = 'jonwage@gmail.com';
            $user->password      = 'changeme';
            $user->save();

            $this->expectExceptionMessage('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: ticket_255__user.username, ticket_255__user.email_address');

            $user                = new \Ticket_255_User();
            $user->username      = 'jwage';
            $user->email_address = 'jonwage@gmail.com';
            $user->password      = 'changeme';
            $user->save();

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, false);
        }

        public function testTest2()
        {
            $dbh  = new \Doctrine1\Adapter\Mock('mysql');
            $conn = \Doctrine1\Manager::connection($dbh);
            $sql  = $conn->export->exportClassesSql([\Ticket_255_User::class]);

            $this->assertEquals($sql[0], 'CREATE TABLE ticket_255__user (id BIGINT AUTO_INCREMENT, username VARCHAR(255), email_address VARCHAR(255), password VARCHAR(255), UNIQUE INDEX username_email_address_unqidx_idx (username, email_address), PRIMARY KEY(id)) ENGINE = INNODB');
        }
    }
}

namespace {
    class Ticket_255_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('email_address', 'string', 255);
            $this->hasColumn('password', 'string', 255);

            $this->unique(['username', 'email_address']);
        }
    }
}
