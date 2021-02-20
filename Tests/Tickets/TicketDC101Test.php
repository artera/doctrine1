<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC101Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $dbh = new \Doctrine_Adapter_Mock('mysql');

            $conn = \Doctrine_Manager::getInstance()->connection($dbh, 'mysql', false);

            $sql = $conn->export->exportSortedClassesSql(['Ticket_DC101_User', 'Ticket_DC101_Profile'], false);
            $this->assertEquals($sql[2], 'ALTER TABLE ticket__d_c101__profile ADD CONSTRAINT user_id_fk FOREIGN KEY (user_id) REFERENCES ticket__d_c101__user(id)');
        }
    }
}

namespace {
    class Ticket_DC101_User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_DC101_Profile as Profile',
                [
                'local'   => 'id',
                'foreign' => 'user_id'
                ]
            );
        }
    }

    class Ticket_DC101_Profile extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('user_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_DC101_User as User',
                [
                'local'          => 'user_id',
                'foreign'        => 'id',
                'foreignKeyName' => 'user_id_fk'
                ]
            );
        }
    }
}
