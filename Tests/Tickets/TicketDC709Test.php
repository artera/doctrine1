<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC709Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testTest()
    {
        $dbh = new \Doctrine_Adapter_Mock('mysql');

        $conn = \Doctrine_Manager::getInstance()->connection($dbh, 'mysql', false);

        $sql = $conn->export->createTableSql(
            'mytable',
            [
            'name' => ['type' => 'string', 'length' => 255, 'comment' => "This comment isn't breaking"]
            ]
        );

        $this->assertEquals($sql[0], "CREATE TABLE mytable (name VARCHAR(255) COMMENT 'This comment isn''t breaking') ENGINE = INNODB");
    }
}
