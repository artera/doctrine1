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
        $this->assertEquals("\$this->setAttribute(\Doctrine1\Core::ATTR_COLL_KEY, 'id');", $code);

        // Boolean values
        $code = trim($builder->buildAttributes(['use_dql_callbacks' => true]));
        $this->assertEquals('$this->setAttribute(\Doctrine1\Core::ATTR_USE_DQL_CALLBACKS, true);', $code);
        $code = trim($builder->buildAttributes(['use_dql_callbacks' => false]));
        $this->assertEquals('$this->setAttribute(\Doctrine1\Core::ATTR_USE_DQL_CALLBACKS, false);', $code);

        // Constant values
        $code = trim($builder->buildAttributes(['model_loading' => 'conservative']));
        $this->assertEquals('$this->setAttribute(\Doctrine1\Core::ATTR_MODEL_LOADING, \Doctrine1\Core::MODEL_LOADING_CONSERVATIVE);', $code);

        $code = trim($builder->buildAttributes(['export' => ['all', 'constraints']]));
        $this->assertEquals('$this->setAttribute(\Doctrine1\Core::ATTR_EXPORT, \Doctrine1\Core::EXPORT_ALL ^ \Doctrine1\Core::EXPORT_CONSTRAINTS);', $code);
    }
}
