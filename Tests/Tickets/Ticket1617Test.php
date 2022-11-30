<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1617Test extends DoctrineUnitTestCase
{
    public function testBuildSchema()
    {
        $import = new \Doctrine1\Import\Schema();
        $array  = $import->buildSchema([__DIR__ . '/1617_schema.yml'], 'yml');

        $this->assertEquals($array['term']['columns']['language']['name'], 'lang as language');
    }
}
