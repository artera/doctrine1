<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1160Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            static::$dbh  = new \Doctrine1\Adapter\Mock('mysql');
            static::$conn = static::$manager->openConnection(static::$dbh);

            $sql = static::$conn->export->exportClassesSql(['Ticket_1160_Test']);
            $this->assertEquals($sql[0], 'CREATE TABLE ticket_1160__test (id BIGINT AUTO_INCREMENT, name VARCHAR(255), PRIMARY KEY(id)) COLLATE latin1_german2_ci ENGINE = MYISAM');
        }
    }
}

namespace {
    class Ticket_1160_Test extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
            $this->getTable()->type = 'MYISAM';
            $this->getTable()->collate = 'latin1_german2_ci';
        }
    }
}
