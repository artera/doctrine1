<?php
namespace Tests\Export;

use Tests\DoctrineUnitTestCase;

class CheckConstraintTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    public static function prepareTables(): void
    {
    }

    public function testCheckConstraints()
    {
        $e = static::$conn->export;

        $sql = $e->exportClassesSql(['CheckConstraintTest']);

        $this->assertEquals($sql[0], 'CREATE TABLE check_constraint_test (id INTEGER PRIMARY KEY AUTOINCREMENT, price DECIMAL(2,2), discounted_price DECIMAL(2,2), CHECK (price >= 100), CHECK (price <= 5000), CHECK (price > discounted_price))');

        $dbh = new \PDO('sqlite::memory:');
        $dbh->exec($sql[0]);
    }
}
