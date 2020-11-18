<?php
namespace Tests\Export;

use Tests\DoctrineUnitTestCase;

class ExportTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Sqlite';

    public function testCreateTableThrowsExceptionWithoutValidTableName()
    {
        $this->expectException(\Doctrine_Export_Exception::class);
        static::$conn->export->createTable(0, [], []);
    }
    public function testCreateTableThrowsExceptionWithEmptyFieldsArray()
    {
        $this->expectException(\Doctrine_Export_Exception::class);
        static::$conn->export->createTable('sometable', [], []);
    }
    public function testDropConstraintExecutesSql()
    {
        static::$conn->export->dropConstraint('sometable', 'relevancy');

        $this->assertEquals(static::$adapter->pop(), 'ALTER TABLE sometable DROP CONSTRAINT relevancy');
    }
    public function testCreateIndexExecutesSql()
    {
        static::$conn->export->createIndex('sometable', 'relevancy', ['fields' => ['title' => [], 'content' => []]]);

        $this->assertEquals(static::$adapter->pop(), 'CREATE INDEX relevancy_idx ON sometable (title, content)');
    }

    public function testDropIndexExecutesSql()
    {
        static::$conn->export->dropIndex('sometable', 'relevancy');

        $this->assertEquals(static::$adapter->pop(), 'DROP INDEX relevancy_idx');
    }
    public function testDropTableExecutesSql()
    {
        static::$conn->export->dropTable('sometable');

        $this->assertEquals(static::$adapter->pop(), 'DROP TABLE sometable');
    }
    public function testDropDottedForeignKey()
    {
        static::$conn->export->dropForeignKey('sometable', 'normal_foreign_key');
        $this->assertEquals(static::$adapter->pop(), 'ALTER TABLE sometable DROP CONSTRAINT normal_foreign_key');

        static::$conn->export->dropForeignKey('sometable', 'dotted.foreign.key');
        $this->assertEquals(static::$adapter->pop(), 'ALTER TABLE sometable DROP CONSTRAINT dotted_foreign_key');
    }
}
