<?php

namespace Tests;

use Doctrine1\Task\GenerateModelsDb;

class CliTest extends DoctrineUnitTestCase
{
    public function testTaskRegistration()
    {
        $cli = new \Doctrine1\Cli();
        $this->assertTrue($cli->taskNameIsRegistered('generate-models-db', $taskClassName));
        $this->assertEquals(GenerateModelsDb::class, $taskClassName);
    }
}
