<?php

namespace Tests\Tickets;

use Doctrine1\Column;
use Doctrine1\Column\Type;
use Tests\DoctrineUnitTestCase;

class TicketDC709Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testTest()
    {
        $dbh = new \Doctrine1\Adapter\Mock('mysql');

        $conn = \Doctrine1\Manager::getInstance()->connection($dbh, 'mysql');

        $sql = $conn->export->createTableSql(
            'mytable',
            [
                new Column('name', Type::String, 255, comment: "This comment isn't breaking"),
            ]
        );

        $this->assertEquals($sql[0], "CREATE TABLE mytable (name VARCHAR(255) COMMENT 'This comment isn''t breaking') ENGINE = InnoDB");
    }
}
