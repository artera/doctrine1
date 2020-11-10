<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2160Test extends DoctrineUnitTestCase
{
    public function testGermanCharactersAreConvertedCorrectly()
    {
        $this->assertEquals(\Doctrine_Inflector::urlize('Ästhetik'), 'aesthetik');
        $this->assertEquals(\Doctrine_Inflector::urlize('ästhetisch'), 'aesthetisch');
        $this->assertEquals(\Doctrine_Inflector::urlize('Übung'), 'uebung');
        $this->assertEquals(\Doctrine_Inflector::urlize('über'), 'ueber');
        $this->assertEquals(\Doctrine_Inflector::urlize('Öl'), 'oel');
        $this->assertEquals(\Doctrine_Inflector::urlize('ölig'), 'oelig');
        $this->assertEquals(\Doctrine_Inflector::urlize('Fuß'), 'fuss');
    }
}
