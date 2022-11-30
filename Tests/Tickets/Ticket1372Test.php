<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1372Test extends DoctrineUnitTestCase
    {
        /* Test array of SQL queries to ensure uniqueness of queries */
        public function testExportSql()
        {
            $drivers = ['mysql',
                         'sqlite',
                         'pgsql',
                        ];

            foreach ($drivers as $driver) {
                $dbh = new \Doctrine1\Adapter\Mock($driver);

                $conn = \Doctrine1\Manager::getInstance()->connection($dbh, $driver);

                $sql = $conn->export->exportSortedClassesSql(['Ticket_1372_ParentClass', 'Ticket_1372_Child_1', 'Ticket_1372_Child_2'], false);

                $this->assertEquals($sql, array_unique($sql));

                $conn = null;

                $dbh = null;
            }
        }
    }

}

namespace {
    class Ticket_1372_ParentClass extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('parent_class');

            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true, 'type' => 'integer', 'length' => '4']);
            $this->hasColumn('type', 'integer', null, ['unique' => true, 'notnull' => true, 'type' => 'integer']);
            $this->hasColumn('value_1', 'integer', 4, ['type' => 'integer', 'length' => '4']);
            $this->hasColumn('value_2', 'integer', 4, ['type' => 'integer', 'length' => '4']);

            $this->index('type_idx', ['fields' => [0 => 'type']]);
            $this->index('values_idx', ['fields' => [0 => 'value_1', 1 => 'value_2']]);

            $this->setAttribute(\Doctrine1\Core::ATTR_EXPORT, \Doctrine1\Core::EXPORT_ALL);

            $this->setSubClasses(['Child_1' => ['type' => 1], 'Child_2' => ['type' => 2]]);
        }
    }
    class Ticket_1372_Child_1 extends Ticket_1372_ParentClass
    {
    }

    class Ticket_1372_Child_2 extends Ticket_1372_ParentClass
    {
    }
}
