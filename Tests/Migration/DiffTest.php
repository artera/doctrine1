<?php
namespace Tests\Migration;

use Tests\DoctrineUnitTestCase;

class DiffTest extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $from           = __DIR__ . '/Diff/schema/from.yml';
        $to             = __DIR__ . '/Diff/schema/to.yml';
        $migrationsPath = __DIR__ . '/Diff/migrations';
        \Doctrine_Lib::removeDirectories($migrationsPath);
        \Doctrine_Lib::makeDirectories($migrationsPath);

        $diff    = new \Doctrine_Migration_Diff($from, $to, $migrationsPath);
        $changes = $diff->generateChanges();
        $this->assertEquals('homepage', $changes['dropped_tables']['homepage']['tableName']);
        $this->assertEquals('blog_post', $changes['created_tables']['blog_post']['tableName']);
        $this->assertEquals(['type' => 'integer', 'length' => 8], $changes['created_columns']['profile']['user_id']);
        $this->assertEquals(['type' => 'integer', 'length' => 8], $changes['dropped_columns']['user']['homepage_id']);
        $this->assertEquals(['type' => 'integer', 'length' => 8], $changes['dropped_columns']['user']['profile_id']);
        $this->assertEquals(['type' => 'string', 'length' => 255, 'unique' => true, 'notnull' => true], $changes['changed_columns']['user']['username']);
        $this->assertEquals('user_id', $changes['created_foreign_keys']['profile']['profile_user_id_user_id']['local']);
        $this->assertEquals('user_id', $changes['created_foreign_keys']['blog_post']['blog_post_user_id_user_id']['local']);
        $this->assertEquals('profile_id', $changes['dropped_foreign_keys']['user']['user_profile_id_profile_id']['local']);
        $this->assertEquals('homepage_id', $changes['dropped_foreign_keys']['user']['user_homepage_id_homepage_id']['local']);
        $this->assertEquals(['fields' => ['user_id']], $changes['created_indexes']['blog_post']['blog_post_user_id']);
        $this->assertEquals(['fields' => ['user_id']], $changes['created_indexes']['profile']['profile_user_id']);
        $this->assertEquals(['fields' => ['is_active']], $changes['dropped_indexes']['user']['is_active']);
        $diff->generateMigrationClasses();

        $files = glob($migrationsPath . '/*.php');
        $this->assertEquals(2, count($files));
        $this->assertNotFalse(strpos($files[0], '_version1.php'));
        $this->assertNotFalse(strpos($files[1], '_version2.php'));

        $code1 = file_get_contents($files[0]);
        $this->assertNotFalse(strpos($code1, 'this->dropTable'));
        $this->assertNotFalse(strpos($code1, 'this->createTable'));
        $this->assertNotFalse(strpos($code1, 'this->removeColumn'));
        $this->assertNotFalse(strpos($code1, 'this->addColumn'));
        $this->assertNotFalse(strpos($code1, 'this->changeColumn'));

        $code2 = file_get_contents($files[1]);
        $this->assertNotFalse(strpos($code2, 'this->dropForeignKey'));
        $this->assertNotFalse(strpos($code2, 'this->removeIndex'));
        $this->assertNotFalse(strpos($code2, 'this->addIndex'));
        $this->assertNotFalse(strpos($code2, 'this->createForeignKey'));

        \Doctrine_Lib::removeDirectories($migrationsPath);
    }
}
