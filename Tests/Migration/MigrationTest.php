<?php
namespace Tests\Migration {
    use Tests\DoctrineUnitTestCase;

    class MigrationTest extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'MigrationPhonenumber';
            static::$tables[] = 'MigrationUser';
            static::$tables[] = 'MigrationProfile';
            parent::prepareTables();
        }

        public function testMigration()
        {
            $migration = new \Doctrine1\Migration(__DIR__ . '/migration_classes');
            $this->assertFalse($migration->hasMigrated());
            $migration->setCurrentVersion(3);
            $migration->migrate(0);
            $this->assertEquals($migration->getCurrentVersion(), 0);
            $this->assertEquals($migration->getLatestVersion(), 11);
            $this->assertEquals($migration->getNextVersion(), 12);
            $current = $migration->getCurrentVersion();
            $migration->setCurrentVersion(100);
            $this->assertEquals($migration->getCurrentVersion(), 100);
            $migration->setCurrentVersion($current);

            $migration->migrate(3);
            $this->assertTrue($migration->hasMigrated());
            $this->assertEquals($migration->getCurrentVersion(), 3);
            $this->assertTrue(static::$conn->import->tableExists('migration_phonenumber'));
            $this->assertTrue(static::$conn->import->tableExists('migration_user'));
            $this->assertTrue(static::$conn->import->tableExists('migration_profile'));
            $migration->migrate(4);
            $this->assertFalse(static::$conn->import->tableExists('migration_profile'));

            $migration->migrate(0);
            $this->assertEquals($migration->getCurrentVersion(), 0);
            $this->assertTrue($migration->getMigrationClass(1) instanceof \AddPhonenumber);
            $this->assertTrue($migration->getMigrationClass(2) instanceof \AddUser);
            $this->assertTrue($migration->getMigrationClass(3) instanceof \AddProfile);
            $this->assertTrue($migration->getMigrationClass(4) instanceof \DropProfile);
            $this->assertFalse(static::$conn->import->tableExists('migration_phonenumber'));
            $this->assertFalse(static::$conn->import->tableExists('migration_user'));
            $this->assertFalse(static::$conn->import->tableExists('migration_profile'));
            $this->assertEquals(
                [
                    1  => 'AddPhonenumber',
                    2  => 'AddUser',
                    3  => 'AddProfile',
                    4  => 'DropProfile',
                    5  => 'Test5',
                    6  => 'Test6',
                    7  => 'Test7',
                    8  => 'Test8',
                    9  => 'Test9',
                    10 => 'Test10',
                    11 => 'Test11',
                ],
                $migration->getMigrationClasses()
            );
        }

        public function testMigrateClearsErrors()
        {
            $migration = new \Doctrine1\Migration(__DIR__ . '/migration_classes');
            $migration->setCurrentVersion(3);
            try {
                $migration->migrate(3);
            } catch (\Doctrine1\Migration\Exception $e) {
                $this->assertTrue($migration->hasErrors());
                $this->assertEquals(1, $migration->getNumErrors());
            }

            try {
                $migration->migrate(3);
            } catch (\Doctrine1\Migration\Exception $e) {
                $this->assertTrue($migration->hasErrors());
                $this->assertEquals(1, $migration->getNumErrors());
            }

            $migration->clearErrors();
            $this->assertFalse($migration->hasErrors());
            $this->assertEquals(0, $migration->getNumErrors());
        }
    }
}

namespace {
    class MigrationPhonenumber extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id', 'integer');
            $this->hasColumn('phonenumber', 'string', 255);
        }
    }

    class MigrationUser extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }
    }

    class MigrationProfile extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }
    }
}
