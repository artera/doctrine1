<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket2251Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_2251_TestStringLength';
            parent::prepareTables();
        }

        public function testEmptyStringLengthSQLExport()
        {
            $drivers = [
            'mysql',
            'sqlite',
            'pgsql',
            ];

            $expected = [
            'mysql'  => 'CREATE TABLE test_string_length (id BIGINT AUTO_INCREMENT, test_string TEXT, PRIMARY KEY(id)) ENGINE = INNODB',
            'sqlite' => 'CREATE TABLE test_string_length (id INTEGER PRIMARY KEY AUTOINCREMENT, test_string TEXT)',
            'pgsql'  => 'CREATE TABLE test_string_length (id BIGSERIAL, test_string TEXT, PRIMARY KEY(id))',
            ];

            foreach ($drivers as $driver) {
                $dbh = new \Doctrine1\Adapter\Mock($driver);

                $conn = \Doctrine1\Manager::getInstance()->connection($dbh, $driver);

                list($sql) = $conn->export->exportSortedClassesSql(['Ticket_2251_TestStringLength'], false);

                $this->assertEquals($sql, $expected[$driver]);

                unset($conn);
                unset($dbh);
            }
        }
    }
}

namespace {
    class Ticket_2251_TestStringLength extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('test_string_length');
            $this->hasColumn('test_string', 'string');
        }

        public function setUp(): void
        {
            parent::setUp();
        }
    }
}
