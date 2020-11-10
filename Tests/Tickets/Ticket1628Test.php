<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1628Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        // String values for non-constant values
        $builder = new \Doctrine_Import_Builder();
        $code    = trim($builder->buildAttributes(['coll_key' => 'id']));
        $this->assertEquals($code, "\$this->setAttribute(Doctrine_Core::ATTR_COLL_KEY, 'id');");

        // Boolean values
        $code = trim($builder->buildAttributes(['use_dql_callbacks' => true]));
        $this->assertEquals($code, '$this->setAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true);');
        $code = trim($builder->buildAttributes(['use_dql_callbacks' => false]));
        $this->assertEquals($code, '$this->setAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS, false);');

        // Constant values
        $code = trim($builder->buildAttributes(['model_loading' => 'conservative']));
        $this->assertEquals($code, '$this->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_CONSERVATIVE);');

        $code = trim($builder->buildAttributes(['export' => ['all', 'constraints']]));
        $this->assertEquals($code, '$this->setAttribute(Doctrine_Core::ATTR_EXPORT, Doctrine_Core::EXPORT_ALL ^ Doctrine_Core::EXPORT_CONSTRAINTS);');
    }
}
