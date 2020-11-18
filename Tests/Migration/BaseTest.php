<?php
namespace Tests\Migration {
    use Tests\DoctrineUnitTestCase;

    class BaseTest extends DoctrineUnitTestCase
    {
        public function setUp(): void
        {
        }

        public function testIsAbstract()
        {
            $reflectionClass = new \ReflectionClass('Doctrine_Migration_Base');
            $this->assertTrue($reflectionClass->isAbstract());
        }

        public function testByDefaultHasNoDefaultTableOptions()
        {
            $this->assertEquals([], \Doctrine_Migration_Base::getDefaultTableOptions());
        }

        public function testGetdefaulttableoptionsReturnsTheOptionsSetWithSetdefaulttableoptions()
        {
            $fixtures = [
            [['charset' => 'utf8']],
            [[]],
            ['type' => 'INNODB', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'],
            ];

            foreach ($fixtures as $fixture) {
                \Doctrine_Migration_Base::setDefaultTableOptions($fixture);
                $this->assertEquals($fixture, \Doctrine_Migration_Base::getDefaultTableOptions());
            }
        }

        public function tearDown(): void
        {
            \Doctrine_Migration_Base::setDefaultTableOptions([]);
        }

        public function testCreatetableMergesTheDefaultTableOptionsWithTheSpecifiedOptions()
        {
            $fixtures = [
            [
                'default'  => ['type' => 'INNODB', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'],
                'user'     => [],
                'expected' => ['type' => 'INNODB', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'],
            ],
            [
                'default'  => ['type' => 'INNODB', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'],
                'user'     => ['charset' => 'latin1', 'collate' => 'latin1_general_ci'],
                'expected' => ['type' => 'INNODB', 'charset' => 'latin1', 'collate' => 'latin1_general_ci'],
            ],
            ];

            foreach ($fixtures as $fixture) {
                \Doctrine_Migration_Base_TestCase_TestBase01::setDefaultTableOptions($fixture['default']);
                $migration = new \Doctrine_Migration_Base_TestCase_TestBase01();
                $migration->createTable('anything', [], $fixture['user']);
                $this->assertEquals($fixture['expected'], $migration->mergedOptions);
            }
        }
    }
}

namespace {
    class Doctrine_Migration_Base_TestCase_TestBase01 extends Doctrine_Migration_Base
    {
        public $mergedOptions = [];

        public function table($upDown, $tableName, array $fields = [], array $options = [])
        {
            $this->mergedOptions = $options;
        }
    }
}
