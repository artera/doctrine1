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
            $reflectionClass = new \ReflectionClass('\Doctrine1\Migration\Base');
            $this->assertTrue($reflectionClass->isAbstract());
        }

        public function testByDefaultHasNoDefaultTableOptions()
        {
            $this->assertEquals([], \Doctrine1\Migration\Base::getDefaultTableOptions());
        }

        public function testGetdefaulttableoptionsReturnsTheOptionsSetWithSetdefaulttableoptions()
        {
            $fixtures = [
            [['charset' => 'utf8']],
            [[]],
            ['type' => 'INNODB', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'],
            ];

            foreach ($fixtures as $fixture) {
                \Doctrine1\Migration\Base::setDefaultTableOptions($fixture);
                $this->assertEquals($fixture, \Doctrine1\Migration\Base::getDefaultTableOptions());
            }
        }

        public function tearDown(): void
        {
            \Doctrine1\Migration\Base::setDefaultTableOptions([]);
        }

        public function testCreatetableMergesTheDefaultTableOptionsWithTheSpecifiedOptions()
        {
            $fixtures = [
            [
                'default'  => ['type' => 'InnoDB', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'],
                'user'     => [],
                'expected' => ['type' => 'InnoDB', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'],
            ],
            [
                'default'  => ['type' => 'InnoDB', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci'],
                'user'     => ['charset' => 'latin1', 'collate' => 'latin1_general_ci'],
                'expected' => ['type' => 'InnoDB', 'charset' => 'latin1', 'collate' => 'latin1_general_ci'],
            ],
            ];

            foreach ($fixtures as $fixture) {
                \TestCase_TestBase01::setDefaultTableOptions($fixture['default']);
                $migration = new \TestCase_TestBase01();
                $migration->createTable('anything', [], $fixture['user']);
                $this->assertEquals($fixture['expected'], $migration->mergedOptions);
            }
        }
    }
}

namespace {
    class TestCase_TestBase01 extends \Doctrine1\Migration\Base
    {
        public $mergedOptions = [];

        public function table(string $upDown, string $tableName, array $fields = [], array $options = []): void
        {
            $this->mergedOptions = $options;
        }
    }
}
