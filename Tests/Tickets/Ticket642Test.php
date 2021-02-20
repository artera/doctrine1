<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket642Test extends DoctrineUnitTestCase
    {
        public function testInit()
        {
            static::$dbh  = new \Doctrine_Adapter_Mock('mysql');
            static::$conn = \Doctrine_Manager::getInstance()->openConnection(static::$dbh);
        }


        public function testTest()
        {
            static::$conn->export->exportClasses(['stDummyObj']);
            $queries = static::$dbh->getAll();

            // Default was not being defined, even if notnull was set
            $this->assertEquals("CREATE TABLE st_dummy_obj (id BIGINT AUTO_INCREMENT, startdate DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL, PRIMARY KEY(id)) ENGINE = INNODB", $queries[1]);
        }
    }
}

namespace {
    class stDummyObj extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('st_dummy_obj');
            $this->hasColumn(
                'startDate',
                'timestamp',
                null,
                [
                'notnull' => true,
                'default' => '0000-00-00 00:00:00'
                ]
            );
        }
    }
}
