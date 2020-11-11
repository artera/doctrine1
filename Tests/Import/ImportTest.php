<?php
namespace Tests\Import;

use Tests\DoctrineUnitTestCase;

class ImportTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
    }
    public static function prepareData(): void
    {
    }

    public function testImport()
    {
        static::$dbh = new \PDO('sqlite::memory:');

        static::$dbh->exec('CREATE TABLE import_test_user (id INTEGER PRIMARY KEY, name TEXT)');

        static::$conn = \Doctrine_Manager::connection(static::$dbh, 'tmp123');

        static::$conn->import->importSchema('Import/_files', ['tmp123']);

        $this->assertTrue(file_exists('Import/_files/ImportTestUser.php'));
        $this->assertTrue(file_exists('Import/_files/generated/BaseImportTestUser.php'));
        \Doctrine_Lib::removeDirectories('Import/_files');
    }
}
