<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    /**
     * @requires extension mysqli
     */
    class Ticket1935Test extends DoctrineUnitTestCase
    {
        protected static ?string $driverName = 'Mysql';

        public function setUp(): void
        {
            \Doctrine_Manager::connection('mysql://root:password@localhost/doctrine', 'Mysql');
        }

        public function tearDown(): void
        {
            static::$manager->closeConnection(static::$connection);
        }

        public static function prepareData(): void
        {
        }

        protected static array $tables = ['Ticket_1935_Article'];

        public function testDuplicatedParamsInSubQuery()
        {
            static::$connection->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true);

            $q = \Doctrine_Query::create()->select('COUNT(a.id) as num_records')
                ->from('Ticket_1935_Article a')
                ->having('num_records > 1');
                //$results = $q->execute();
                $this->assertEquals($q->getSqlQuery(), 'SELECT COUNT(`t`.`id`) AS `t__0` FROM `ticket_1935_article` `t` HAVING `t__0` > 1');

            static::$connection->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, false);
        }
    }
}

namespace {
    class Ticket_1935_Article extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('ticket_1935_article');
            $this->hasColumn('title', 'string', 255, ['type' => 'string', 'length' => '255']);
        }
    }
}
