<?php
namespace Tests\Export;

use Tests\DoctrineUnitTestCase;

class RecordTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'mysql';

    public static function prepareTables(): void
    {
    }

    public static function prepareData(): void
    {
    }

    public function testExportSupportsForeignKeys()
    {
        $sql = static::$conn->export->exportClassesSql(['ForeignKeyTest']);

        $this->assertEquals($sql[0], 'CREATE TABLE foreign_key_test (id BIGINT AUTO_INCREMENT, name TEXT, code INT, content TEXT, parent_id BIGINT, INDEX parent_id_idx (parent_id), PRIMARY KEY(id)) ENGINE = INNODB');
        $this->assertTrue(isset($sql[1]));
        $this->assertEquals($sql[1], 'ALTER TABLE foreign_key_test ADD CONSTRAINT foreign_key_test_parent_id_foreign_key_test_id FOREIGN KEY (parent_id) REFERENCES foreign_key_test(id) ON UPDATE RESTRICT ON DELETE CASCADE');
    }

    public function testExportSupportsIndexes()
    {
        $sql = static::$conn->export->exportClassesSql(['MysqlIndexTestRecord']);

        $this->assertEquals($sql[0], 'CREATE TABLE mysql_index_test_record (id BIGINT AUTO_INCREMENT, name TEXT, code INT, content TEXT, FULLTEXT INDEX content_idx (content), UNIQUE INDEX namecode_idx (name, code), PRIMARY KEY(id)) ENGINE = MYISAM');
    }

    public function testRecordDefinitionsSupportTableOptions()
    {
        $sql = static::$conn->export->exportClassesSql(['MysqlTestRecord']);

        $this->assertEquals($sql[0], 'CREATE TABLE mysql_test_record (name TEXT, code BIGINT, PRIMARY KEY(name, code)) ENGINE = INNODB');
    }

    public function testExportSupportsForeignKeysWithoutAttributes()
    {
        $sql = static::$conn->export->exportClassesSql(['ForeignKeyTest']);

        $this->assertEquals($sql[0], 'CREATE TABLE foreign_key_test (id BIGINT AUTO_INCREMENT, name TEXT, code INT, content TEXT, parent_id BIGINT, INDEX parent_id_idx (parent_id), PRIMARY KEY(id)) ENGINE = INNODB');
        $this->assertTrue(isset($sql[1]));
        $this->assertEquals($sql[1], 'ALTER TABLE foreign_key_test ADD CONSTRAINT foreign_key_test_parent_id_foreign_key_test_id FOREIGN KEY (parent_id) REFERENCES foreign_key_test(id) ON UPDATE RESTRICT ON DELETE CASCADE');
    }

    public function testExportSupportsForeignKeysForManyToManyRelations()
    {
        $sql = static::$conn->export->exportClassesSql(['MysqlUser']);

        $this->assertEquals($sql[0], 'CREATE TABLE mysql_user (id BIGINT AUTO_INCREMENT, name TEXT, PRIMARY KEY(id)) ENGINE = INNODB');

        $sql = static::$conn->export->exportClassesSql(['MysqlGroup']);

        $this->assertEquals($sql[0], 'CREATE TABLE mysql_group (id BIGINT AUTO_INCREMENT, name TEXT, PRIMARY KEY(id)) ENGINE = INNODB');
    }

    public function testExportModelFromDirectory()
    {
        \Doctrine_Core::createTablesFromModels(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'export');
        $this->assertEquals(static::$adapter->pop(), 'COMMIT');
        $this->assertEquals(static::$adapter->pop(), 'ALTER TABLE cms__category_languages ADD CONSTRAINT cms__category_languages_category_id_cms__category_id FOREIGN KEY (category_id) REFERENCES cms__category(id) ON DELETE CASCADE');
        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE cms__category_languages (id BIGINT AUTO_INCREMENT, name TEXT, category_id BIGINT, language_id BIGINT, INDEX index_category_idx (category_id), INDEX index_language_idx (language_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = INNODB');
        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE cms__category (id BIGINT AUTO_INCREMENT, created DATETIME, parent BIGINT, position MEDIUMINT, active BIGINT, INDEX index_parent_idx (parent), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = INNODB');
        $this->assertEquals(static::$adapter->pop(), 'BEGIN TRANSACTION');
    }
}
