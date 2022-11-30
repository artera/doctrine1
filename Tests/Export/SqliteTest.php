<?php
namespace Tests\Export;

use Tests\DoctrineUnitTestCase;

class SqliteTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Sqlite';

    public function testCreateDatabaseDoesNotExecuteSqlAndCreatesSqliteFile()
    {
        static::$conn->export->createDatabase('sqlite.db');

        $this->assertTrue(file_exists('sqlite.db'));
    }
    public function testDropDatabaseDoesNotExecuteSqlAndDeletesSqliteFile()
    {
        static::$conn->export->dropDatabase('sqlite.db');

        $this->assertFalse(file_exists('sqlite.db'));
    }
    public function testCreateTableSupportsAutoincPks()
    {
        $name = 'mytable';

        $fields = ['id' => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true]];

        static::$conn->export->createTable($name, $fields);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id INTEGER PRIMARY KEY AUTOINCREMENT)');
    }
    public function testCreateTableSupportsDefaultAttribute()
    {
        $name   = 'mytable';
        $fields = ['name'  => ['type' => 'char', 'length' => 10, 'default' => 'def'],
                         'type' => ['type' => 'integer', 'length' => 3, 'default' => 12]
                         ];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type INTEGER DEFAULT 12, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsMultiplePks()
    {
        $name   = 'mytable';
        $fields = ['name'  => ['type' => 'char', 'length' => 10],
                         'type' => ['type' => 'integer', 'length' => 3]];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type INTEGER, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsIndexes()
    {
        $fields = ['id'    => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true],
                         'name' => ['type' => 'string', 'length' => 4],
                         ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => ['fields' => ['id', 'name']]]
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);

        //this was the old line, but it looks like the table is created first
        //and then the index so i replaced it with the ones below
        //$this->assertEquals($var, 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id, name))');

        $this->assertEquals(static::$adapter->pop(), 'CREATE INDEX myindex_idx ON sometable (id, name)');

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4))');
    }
    public function testIdentifierQuoting()
    {
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, true);

        $fields = ['id'    => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true],
                         'name' => ['type' => 'string', 'length' => 4],
                         ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => ['fields' => ['id', 'name']]]
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);

        //this was the old line, but it looks like the table is created first
        //and then the index so i replaced it with the ones below
        //$this->assertEquals($var, 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id, name))');

        $this->assertEquals(static::$adapter->pop(), 'CREATE INDEX "myindex_idx" ON "sometable" ("id", "name")');

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE "sometable" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" VARCHAR(4))');

        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, false);
    }
    public function testQuoteMultiplePks()
    {
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, true);

        $name   = 'mytable';
        $fields = ['name'  => ['type' => 'char', 'length' => 10],
                         'type' => ['type' => 'integer', 'length' => 3]];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE "mytable" ("name" CHAR(10), "type" INTEGER, PRIMARY KEY("name", "type"))');

        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, false);
    }
    public function testUnknownIndexSortingAttributeThrowsException()
    {
        $fields = ['id'   => ['sorting' => 'ASC'],
                        'name' => ['sorting' => 'unknown']];

        $this->expectException(\Doctrine1\Export\Exception::class);
        static::$conn->export->getIndexFieldDeclarationList($fields);
    }
    public function testCreateTableSupportsIndexesWithCustomSorting()
    {
        $fields = ['id'    => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true, 'unique' => true],
                         'name' => ['type' => 'string', 'length' => 4],
                         ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => [
                                                    'fields' => [
                                                            'id'   => ['sorting' => 'ASC'],
                                                            'name' => ['sorting' => 'DESC']
                                                                ]
                                                            ]]
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);

        //removed this assertion and inserted the two below
//        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id ASC, name DESC))');

        $this->assertEquals(static::$adapter->pop(), 'CREATE INDEX myindex_idx ON sometable (id ASC, name DESC)');

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4))');
    }

    /**
    public function testExportSupportsEmulationOfCascadingDeletes()
    {
        $r = new \ForeignKeyTest;
        $this->assertEquals(static::$adapter->pop(), 'COMMIT');
        $this->assertEquals(static::$adapter->pop(), 'CREATE TRIGGER doctrine_foreign_key_test_cscd_delete AFTER DELETE ON foreign_key_test BEGIN DELETE FROM foreign_key_test WHERE parent_id = old.id;END;');
        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE foreign_key_test (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(2147483647), code INTEGER, content VARCHAR(4000), parent_id INTEGER)');
        $this->assertEquals(static::$adapter->pop(), 'BEGIN TRANSACTION');
    }
    */
}
