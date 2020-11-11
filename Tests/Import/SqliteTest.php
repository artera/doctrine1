<?php
namespace Tests\Import;

use Tests\DoctrineUnitTestCase;

class SqliteTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Sqlite';

    public function testListSequencesExecutesSql()
    {
        static::$conn->import->listSequences('table');

        $this->assertEquals(static::$adapter->pop(), "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name");
    }
    public function testListTableColumnsExecutesSql()
    {
        static::$conn->import->listTableColumns('table');

        $this->assertEquals(static::$adapter->pop(), 'PRAGMA table_info(table)');
    }
    public function testListTableIndexesExecutesSql()
    {
        static::$conn->import->listTableIndexes('table');

        $this->assertEquals(static::$adapter->pop(), 'PRAGMA index_list(table)');
    }
    public function testListTablesExecutesSql()
    {
        static::$conn->import->listTables();

        $q = "SELECT name FROM sqlite_master WHERE type = 'table' AND name != 'sqlite_sequence' UNION ALL SELECT name FROM sqlite_temp_master WHERE type = 'table' ORDER BY name";

        $this->assertEquals(static::$adapter->pop(), $q);
    }
}
