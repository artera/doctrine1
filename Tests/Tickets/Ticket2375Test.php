<?php

namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2375Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $models1Dir = dirname(__FILE__) . '/2375/models1';
        $models2Dir = dirname(__FILE__) . '/2375/models2';

        // try loading a couple initial models

        $models1 = \Doctrine1\Core::loadModels($models1Dir);
        //$models2 = \Doctrine1\Core::loadModels($models2Dir);

        // make sure two models were loaded
        $this->assertEquals(2, count($models1));

        // make sure the right models were loaded
        $this->assertTrue(key_exists('Ticket_2375_Model1', $models1));
        $this->assertTrue(key_exists('Ticket_2375_Model2', $models1));

        // get a list of all models that have been loaded
        $loadedModels = \Doctrine1\Core::getLoadedModelFiles();

        // make sure the paths are correct
        $this->assertEquals($loadedModels['Ticket_2375_Model1'], $models1Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model1.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model2'], $models1Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model2.php');

        // try loading a few more models

        $models2 = \Doctrine1\Core::loadModels($models2Dir);

        // make sure the right models were loaded
        $this->assertTrue(key_exists('Ticket_2375_Model3', $models2));
        $this->assertTrue(key_exists('Ticket_2375_Model4', $models2));
        $this->assertTrue(key_exists('Ticket_2375_Model5', $models2));

        // get a list of all models that have been loaded
        $loadedModels = \Doctrine1\Core::getLoadedModelFiles();

        // make sure the paths are correct
        $this->assertEquals($loadedModels['Ticket_2375_Model1'], $models1Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model1.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model2'], $models1Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model2.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model3'], $models2Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model3.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model4'], $models2Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model4.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model5'], $models2Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model5.php');

        // try loading the first models again

        $models1 = \Doctrine1\Core::loadModels($models1Dir);

        // make sure the right models were loaded
        $this->assertTrue(key_exists('Ticket_2375_Model1', $models1));
        $this->assertTrue(key_exists('Ticket_2375_Model2', $models1));

        // get a list of all models that have been loaded
        $loadedModels = \Doctrine1\Core::getLoadedModelFiles();

        // make sure the paths are correct
        $this->assertEquals($loadedModels['Ticket_2375_Model1'], $models1Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model1.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model2'], $models1Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model2.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model3'], $models2Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model3.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model4'], $models2Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model4.php');
        $this->assertEquals($loadedModels['Ticket_2375_Model5'], $models2Dir . DIRECTORY_SEPARATOR . 'Ticket_2375_Model5.php');
    }
}
