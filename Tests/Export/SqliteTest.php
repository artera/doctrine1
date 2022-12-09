<?php

namespace Tests\Export;

use Doctrine1\Column;
use Doctrine1\Column\Type;
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

        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true),
        ];

        static::$conn->export->createTable($name, $fields);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id INTEGER PRIMARY KEY AUTOINCREMENT)');
    }
    public function testCreateTableSupportsDefaultAttribute()
    {
        $name   = 'mytable';
        $fields = [
            new Column('name', Type::String, 10, fixed: true, default: 'def'),
            new Column('type', Type::Integer, 3, default: 12),
        ];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (name CHAR(10) DEFAULT \'def\', type INTEGER DEFAULT 12, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsMultiplePks()
    {
        $name   = 'mytable';
        $fields = [
            new Column('name', Type::String, 10, fixed: true),
            new Column('type', Type::Integer, 3),
        ];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type INTEGER, PRIMARY KEY(name, type))');
    }
    public function testCreateTableSupportsIndexes()
    {
        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true, unique: true),
            new Column('name', Type::String, 4),
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
        static::$conn->setQuoteIdentifier(true);

        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true, unique: true),
            new Column('name', Type::String, 4),
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

        static::$conn->setQuoteIdentifier(false);
    }
    public function testQuoteMultiplePks()
    {
        static::$conn->setQuoteIdentifier(true);

        $name   = 'mytable';
        $fields = [
            new Column('name', Type::String, 10, fixed: true),
            new Column('type', Type::Integer, 3),
        ];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE "mytable" ("name" CHAR(10), "type" INTEGER, PRIMARY KEY("name", "type"))');

        static::$conn->setQuoteIdentifier(false);
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
        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true, unique: true),
            new Column('name', Type::String, 4),
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
}
