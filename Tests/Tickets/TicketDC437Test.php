<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC437Test extends DoctrineUnitTestCase
    {
        private static function prepareConnections()
        {
            // Establish two new \individual connections
            $dsn = 'sqlite::memory:';
            $dbh = new \PDO($dsn);
            static::$manager->openConnection($dbh, 'conn1', false);

            $dsn = 'sqlite::memory:';
            $dbh = new \PDO($dsn);
            static::$manager->openConnection($dbh, 'conn2', false);
        }

        public static function prepareTables(): void
        {
            // Don't see any better place to perform connection preparation
            static::prepareConnections();

            static::$tables   = [];
            static::$tables[] = 'Doctrine_Ticket_DC437_Record';

            /* Export classes for each of the existing connections.
             *
             * To trick \Doctrine_Export::exportClasses() implementation to use
             * a proper connection, we need to manually re-bind all the components.
             */
            foreach (['conn1', 'conn2'] as $dbConnName) {
                $dbConn = static::$manager->getConnection($dbConnName);
                foreach (static::$tables as $componentName) {
                    static::$manager->bindComponent($componentName, $dbConn->getName());
                }
                $dbConn->export->exportClasses(static::$tables);
            }
        }

        public static function prepareData(): void
        {
            $conn1 = static::$manager->getConnection('conn1');
            $conn2 = static::$manager->getConnection('conn2');

            // Create 1 record using conn1, and 2 records using conn2
            $r1 = new \Doctrine_Ticket_DC437_Record();
            $r1->save($conn1);

            $r2 = new \Doctrine_Ticket_DC437_Record();
            $r2->save($conn2);
            $r3 = new \Doctrine_Ticket_DC437_Record();
            $r3->save($conn2);
        }

        public function testConnectionQuery()
        {
            /* Let's retrieve all the records twice using different connections.
             *
             * Since we have created a different number of records for different
             * connections (databases), we expect to get different values here.
             *
             * But due to the bug both queries will be performed using the same
             * connection.
             */

            $conn1 = static::$manager->getConnection('conn1');
            $conn2 = static::$manager->getConnection('conn2');

            $dql = 'FROM Doctrine_Ticket_DC437_Record';

            $rs1 = $conn1->query($dql);
            $rs2 = $conn2->query($dql);

            $this->assertNotEquals(count($rs1), count($rs2));
        }
    }
}

namespace {
    class Doctrine_Ticket_DC437_Record extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('dc437records');

            $this->hasColumn('id', 'integer', 5, [
            'unsigned'      => true,
            'notnull'       => true,
            'primary'       => true,
            'autoincrement' => true
            ]);

            $this->hasColumn('test', 'string');
        }
    }
}
