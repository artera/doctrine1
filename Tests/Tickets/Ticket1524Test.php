<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1524Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $manager = \Doctrine_Manager::getInstance();
            $manager->registerValidators(['MyValidator']);
            $this->assertTrue(in_array('MyValidator', $manager->getValidators()));
        }
    }
}

namespace {
    class MyValidator extends Doctrine_Validator
    {
        public function validate($value)
        {
            return true;
        }
    }
}
