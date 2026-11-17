<?php

namespace Tests\Import;

use Tests\DoctrineUnitTestCase;

class MysqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Mysql';

    public function testListTablesExcludesViews(): void
    {
        static::$conn->import->listTables();

        $this->assertEquals("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'", static::$adapter->pop());
    }
}
