<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1727Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $models1 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models1', \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $models2 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models1', \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $this->assertEquals($models1, $models2);

        $models1 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models1');
        $models2 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models1');
        $this->assertEquals($models1, $models2);

        $models1 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models1', \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $models2 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models1', \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $this->assertEquals($models1, $models2);

        $models1 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models2', \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $models2 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models2', \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $this->assertEquals($models1, $models2);

        $models1 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models2');
        $models2 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models2');
        $this->assertEquals($models1, $models2);

        $models1 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models2', \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $models2 = \Doctrine_Core::loadModels(__DIR__ . '/1727/models2', \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $this->assertEquals($models1, $models2);

        $models1 = \Doctrine_Core::loadModels([__DIR__ . '/1727/models1', __DIR__ . '/1727/models2']);
        $models2 = \Doctrine_Core::loadModels([__DIR__ . '/1727/models1', __DIR__ . '/1727/models2']);
        $this->assertEquals($models1, $models2);

        $models1 = \Doctrine_Core::loadModels([__DIR__ . '/1727/models1', __DIR__ . '/1727/models2'], \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $models2 = \Doctrine_Core::loadModels([__DIR__ . '/1727/models1', __DIR__ . '/1727/models2'], \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
        $this->assertEquals($models1, $models2);
    }
}
