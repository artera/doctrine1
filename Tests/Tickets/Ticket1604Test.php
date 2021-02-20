<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1604Test extends DoctrineUnitTestCase
    {
        public function testExport()
        {
            $conn = \Doctrine_Manager::connection('mysql://root@localhost/test');
            $sql  = $conn->export->exportClassesSql(['Ticket_1604_User', 'Ticket_1604_EmailAdresses']);

            $def = [
            'CREATE TABLE ticket_1604__user (id BIGINT AUTO_INCREMENT, name VARCHAR(30), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = INNODB',
            'CREATE TABLE ticket_1604__email_adresses (id BIGINT AUTO_INCREMENT, user_id BIGINT, address VARCHAR(30), INDEX user_id_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = INNODB',
            'ALTER TABLE ticket_1604__email_adresses ADD CONSTRAINT ticket_1604__email_adresses_user_id_ticket_1604__user_id FOREIGN KEY (user_id) REFERENCES ticket_1604__user(id)'
            ];

            $this->assertEquals($sql, $def);
        }
    }
}

namespace {
    class Ticket_1604_User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 30);

            $this->option('type', 'INNODB');
            $this->option('collate', 'utf8_unicode_ci');
            $this->option('charset', 'utf8');
        }

        public function setUp(): void
        {
            $this->hasMany('Ticket_1604_EmailAdresses as emailAdresses', ['local' => 'id', 'foreign' => 'userId', 'onDelete' => 'CASCADE']);
        }
    }

    class Ticket_1604_EmailAdresses extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id as userId', 'integer');
            $this->hasColumn('address', 'string', 30);

            $this->option('type', 'INNODB');
            $this->option('collate', 'utf8_unicode_ci');
            $this->option('charset', 'utf8');
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_1604_User as user', ['local' => 'userId', 'foreign' => 'id']);
        }
    }
}
