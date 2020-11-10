<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1617Test extends DoctrineUnitTestCase
{
    public function testBuildSchema()
    {
        $import = new \Doctrine_Import_Schema();
        $array  = $import->buildSchema('Tickets/1617_schema.yml', 'yml');

        $this->assertEquals($array['term']['columns']['language']['name'], 'lang as language');
    }
}
