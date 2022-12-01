<?php

namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1628Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        // String values for non-constant values
        $builder = new \Doctrine1\Import\Builder();
        $code    = trim($builder->buildAttributes(['coll_key' => 'id']));
        $this->assertEquals("\$this->setCollectionKey('id');", $code);

        // Boolean values
        $code = trim($builder->buildAttributes(['use_dql_callbacks' => true]));
        $this->assertEquals('$this->setUseDqlCallbacks(true);', $code);
        $code = trim($builder->buildAttributes(['use_dql_callbacks' => false]));
        $this->assertEquals('$this->setUseDqlCallbacks(false);', $code);

        $code = trim($builder->buildAttributes(['export' => ['all', 'constraints']]));
        $this->assertEquals('$this->setExportFlags(\Doctrine1\Core::EXPORT_ALL ^ \Doctrine1\Core::EXPORT_CONSTRAINTS);', $code);
    }
}
