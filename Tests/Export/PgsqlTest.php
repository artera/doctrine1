<?php

namespace Tests\Export;

use Doctrine1\Column;
use Doctrine1\Column\Type;
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

        $fields  = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true),
        ];
        $options = ['primary' => ['id']];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id BIGSERIAL, PRIMARY KEY(id))');
    }
    public function testQuoteAutoincPks()
    {
        static::$conn->setQuoteIdentifier(true);

        $name = 'mytable';

        $fields  = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true),
        ];
        $options = ['primary' => ['id']];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE "mytable" ("id" BIGSERIAL, PRIMARY KEY("id"))');

        $name   = 'mytable';
        $fields  = [
            new Column('name', Type::String, 10, fixed: true),
            new Column('type', Type::Integer, 3),
        ];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE "mytable" ("name" CHAR(10), "type" INT, PRIMARY KEY("name", "type"))');

        static::$conn->setQuoteIdentifier(false);
    }
    public function testForeignKeyIdentifierQuoting()
    {
        static::$conn->setQuoteIdentifier(true);

        $name = 'mytable';

        $fields  = [
            new Column('id', Type::Boolean, primary: true),
            new Column('foreignKey', Type::Integer),
        ];
        $options = ['foreignKeys' => [['local'        => 'foreignKey',
                                                      'foreign'      => 'id',
                                                      'foreignTable' => 'sometable']]
                         ];


        $sql = static::$conn->export->createTableSql($name, $fields, $options);

        $this->assertEquals($sql[0], 'CREATE TABLE "mytable" ("id" BOOLEAN, "foreignKey" BIGINT)');
        $this->assertEquals($sql[1], 'ALTER TABLE "mytable" ADD FOREIGN KEY ("foreignKey") REFERENCES "sometable"("id") NOT DEFERRABLE INITIALLY IMMEDIATE');

        static::$conn->setQuoteIdentifier(false);
    }
    public function testCreateTableSupportsDefaultAttribute()
    {
        $name   = 'mytable';
        $fields  = [
            new Column('name', Type::String, 10, fixed: true, default: 'def'),
            new Column('type', Type::Integer, 3, default: 12),
            new Column('is_active', Type::Boolean, default: 'false'),
            new Column('is_admin', Type::Boolean, default: 'true'),
        ];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), "CREATE TABLE mytable (name CHAR(10) DEFAULT 'def', type INT DEFAULT 12, is_active BOOLEAN DEFAULT 'false', is_admin BOOLEAN DEFAULT 'true', PRIMARY KEY(name, type))");
    }
    public function testCreateTableSupportsMultiplePks()
    {
        $name   = 'mytable';
        $fields  = [
            new Column('name', Type::String, 10, fixed: true),
            new Column('type', Type::Integer, 3),
        ];

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
                'CREATE TABLE foo_foreignly_owned (id BIGSERIAL, name VARCHAR(200), fooId BIGINT, PRIMARY KEY(id))',
                'CREATE TABLE foo_bar_record (fooId BIGINT, barId BIGINT, PRIMARY KEY(fooId, barId))',
                'CREATE TABLE foo (id BIGSERIAL, name VARCHAR(200) NOT NULL, parent_id BIGINT, local_foo BIGINT, PRIMARY KEY(id))',
                'CREATE TABLE bar (id BIGSERIAL, name VARCHAR(200), PRIMARY KEY(id))',
                'ALTER TABLE foo_reference ADD CONSTRAINT foo_reference_foo1_foo_id FOREIGN KEY (foo1) REFERENCES foo(id) NOT DEFERRABLE INITIALLY IMMEDIATE',
                'ALTER TABLE foo_bar_record ADD CONSTRAINT foo_bar_record_fooId_foo_id FOREIGN KEY (fooId) REFERENCES foo(id) NOT DEFERRABLE INITIALLY IMMEDIATE',
                'ALTER TABLE foo ADD CONSTRAINT foo_parent_id_foo_id FOREIGN KEY (parent_id) REFERENCES foo(id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE',
                'ALTER TABLE foo ADD CONSTRAINT foo_local_foo_foo_locally_owned_id FOREIGN KEY (local_foo) REFERENCES foo_locally_owned(id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE',
            ],
            $sql
        );
    }

    public function testAlterTableSql()
    {
        $changes = [
            'add'    => [new Column('newfield', Type::Integer)],
            'remove' => ['oldfield'],
        ];

        $sql = static::$conn->export->alterTableSql('mytable', $changes);

        $this->assertEquals(
            [
                'ALTER TABLE mytable ADD newfield BIGINT',
                'ALTER TABLE mytable DROP oldfield'
            ],
            $sql
        );
    }

    public function testAlterTableSqlIdentifierQuoting()
    {
        static::$conn->setQuoteIdentifier(true);

        $changes = [
            'add'    => [new Column('newfield', Type::Integer)],
            'remove' => ['oldfield'],
        ];

        $sql = static::$conn->export->alterTableSql('mytable', $changes);

        $this->assertEquals(
            [
                'ALTER TABLE "mytable" ADD "newfield" BIGINT',
                'ALTER TABLE "mytable" DROP "oldfield"'
            ],
            $sql
        );
    }
}
