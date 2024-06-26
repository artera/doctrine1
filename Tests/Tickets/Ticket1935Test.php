<?php

namespace Tests\Tickets {

    use PHPUnit\Framework\Attributes\RequiresPhpExtension;
    use Tests\DoctrineUnitTestCase;

    #[RequiresPhpExtension('mysqli')]
    class Ticket1935Test extends DoctrineUnitTestCase
    {
        protected static ?string $driverName = 'Mysql';

        public function setUp(): void
        {
            \Doctrine1\Manager::connection('mysql://root:password@localhost/doctrine', 'Mysql');
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
            static::$connection->setQuoteIdentifier(true);

            $q = \Doctrine1\Query::create()->select('COUNT(a.id) as num_records')
                ->from('Ticket_1935_Article a')
                ->having('num_records > 1');
            //$results = $q->execute();
            $this->assertEquals($q->getSqlQuery(), 'SELECT COUNT(`t`.`id`) AS `t__0` FROM `ticket_1935_article` `t` HAVING `t__0` > 1');

            static::$connection->setQuoteIdentifier(false);
        }
    }
}

namespace {
    class Ticket_1935_Article extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('ticket_1935_article');
            $this->hasColumn('title', 'string', 255, ['type' => 'string', 'length' => '255']);
        }
    }
}
