<?php
namespace Tests\Export;

use Tests\DoctrineUnitTestCase;

class PgsqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Pgsql';

    public function testCreateDatabaseExecutesSql()
    {
        static::$conn->export->createDatabase('db');
        $this->assertEquals(static::$adapter->pop(), 'CREATE DATABASE db');
    }
    public function testDropDatabaseExecutesSql()
    {
        static::$conn->export->dropDatabase('db');

        $this->assertEquals(static::$adapter->pop(), 'DROP DATABASE db');
    }
    public function testCreateTableSupportsAutoincPks()
    {
        $name = 'mytable';

        $fields  = ['id' => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true]];
        $options = ['primary' => ['id']];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id SERIAL, PRIMARY KEY(id))');
    }
    public function testQuoteAutoincPks()
    {
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, true);

        $name = 'mytable';

        $fields  = ['id' => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true]];
        $options = ['primary' => ['id']];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE "mytable" ("id" SERIAL, PRIMARY KEY("id"))');

        $name   = 'mytable';
        $fields = ['name'  => ['type' => 'char', 'length' => 10],
                         'type' => ['type' => 'integer', 'length' => 3]];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE "mytable" ("name" CHAR(10), "type" INT, PRIMARY KEY("name", "type"))');

        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, false);
    }
    public function testForeignKeyIdentifierQuoting()
    {
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, true);

        $name = 'mytable';

        $fields = ['id'         => ['type' => 'boolean', 'primary' => true],
                        'foreignKey' => ['type' => 'integer']
                        ];
        $options = ['foreignKeys' => [['local'        => 'foreignKey',
                                                      'foreign'      => 'id',
                                                      'foreignTable' => 'sometable']]
                         ];


        $sql = static::$conn->export->createTableSql($name, $fields, $options);

        $this->assertEquals($sql[0], 'CREATE TABLE "mytable" ("id" BOOLEAN, "foreignKey" INT)');
        $this->assertEquals($sql[1], 'ALTER TABLE "mytable" ADD FOREIGN KEY ("foreignKey") REFERENCES "sometable"("id") NOT DEFERRABLE INITIALLY IMMEDIATE');

        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, false);
    }
    public function testCreateTableSupportsDefaultAttribute()
    {
        $name   = 'mytable';
        $fields = ['name'       => ['type' => 'char', 'length' => 10, 'default' => 'def'],
                         'type'      => ['type' => 'integer', 'length' => 3, 'default' => 12],
                         'is_active' => ['type' => 'boolean', 'default' => '0'],
                         'is_admin'  => ['type' => 'boolean', 'default' => 'true'],
                         ];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), "CREATE TABLE mytable (name CHAR(10) DEFAULT 'def', type INT DEFAULT 12, is_active BOOLEAN DEFAULT 'false', is_admin BOOLEAN DEFAULT 'true', PRIMARY KEY(name, type))");
    }
    public function testCreateTableSupportsMultiplePks()
    {
        $name   = 'mytable';
        $fields = ['name'  => ['type' => 'char', 'length' => 10],
                         'type' => ['type' => 'integer', 'length' => 3]];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type INT, PRIMARY KEY(name, type))');
    }
    public function testExportSql()
    {
        $sql = static::$conn->export->exportClassesSql(['FooRecord', 'FooReferenceRecord', 'FooLocallyOwned', 'FooForeignlyOwned', 'FooForeignlyOwnedWithPk', 'FooBarRecord', 'BarRecord']);

        $this->assertEquals(
            [
                'CREATE TABLE foo_reference (foo1 BIGINT, foo2 BIGINT, PRIMARY KEY(foo1, foo2))',
                'CREATE TABLE foo_locally_owned (id BIGSERIAL, name VARCHAR(200), PRIMARY KEY(id))',
                'CREATE TABLE foo_foreignly_owned_with_pk (id BIGSERIAL, name VARCHAR(200), PRIMARY KEY(id))',
                'CREATE TABLE foo_foreignly_owned (id BIGSERIAL, name VARCHAR(200), fooid BIGINT, PRIMARY KEY(id))',
                'CREATE TABLE foo_bar_record (fooid BIGINT, barid BIGINT, PRIMARY KEY(fooid, barid))',
                'CREATE TABLE foo (id BIGSERIAL, name VARCHAR(200) NOT NULL, parent_id BIGINT, local_foo BIGINT, PRIMARY KEY(id))',
                'CREATE TABLE bar (id BIGSERIAL, name VARCHAR(200), PRIMARY KEY(id))',
                'ALTER TABLE foo_reference ADD CONSTRAINT foo_reference_foo1_foo_id FOREIGN KEY (foo1) REFERENCES foo(id) NOT DEFERRABLE INITIALLY IMMEDIATE',
                'ALTER TABLE foo_bar_record ADD CONSTRAINT foo_bar_record_fooid_foo_id FOREIGN KEY (fooid) REFERENCES foo(id) NOT DEFERRABLE INITIALLY IMMEDIATE',
                'ALTER TABLE foo ADD CONSTRAINT foo_parent_id_foo_id FOREIGN KEY (parent_id) REFERENCES foo(id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE',
                'ALTER TABLE foo ADD CONSTRAINT foo_local_foo_foo_locally_owned_id FOREIGN KEY (local_foo) REFERENCES foo_locally_owned(id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE',
            ],
            $sql
        );
    }

    public function testAlterTableSql()
    {
        $changes = [
            'add'    => ['newfield' => ['type' => 'int']],
            'remove' => ['oldfield' => []]
        ];

        $sql = static::$conn->export->alterTableSql('mytable', $changes);

        $this->assertEquals(
            [
                'ALTER TABLE mytable ADD newfield INT',
                'ALTER TABLE mytable DROP oldfield'
            ],
            $sql
        );
    }

    public function testAlterTableSqlIdentifierQuoting()
    {
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUOTE_IDENTIFIER, true);

        $changes = [
            'add'    => ['newfield' => ['type' => 'int']],
            'remove' => ['oldfield' => []]
        ];

        $sql = static::$conn->export->alterTableSql('mytable', $changes);

        $this->assertEquals(
            [
                'ALTER TABLE "mytable" ADD "newfield" INT',
                'ALTER TABLE "mytable" DROP "oldfield"'
            ],
            $sql
        );
    }
}
