<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2160Test extends DoctrineUnitTestCase
{
    public function testGermanCharactersAreConvertedCorrectly()
    {
        $this->assertEquals(\Doctrine1\Inflector::urlize('Ästhetik'), 'aesthetik');
        $this->assertEquals(\Doctrine1\Inflector::urlize('ästhetisch'), 'aesthetisch');
        $this->assertEquals(\Doctrine1\Inflector::urlize('Übung'), 'uebung');
        $this->assertEquals(\Doctrine1\Inflector::urlize('über'), 'ueber');
        $this->assertEquals(\Doctrine1\Inflector::urlize('Öl'), 'oel');
        $this->assertEquals(\Doctrine1\Inflector::urlize('ölig'), 'oelig');
        $this->assertEquals(\Doctrine1\Inflector::urlize('Fuß'), 'fuss');
    }
}
