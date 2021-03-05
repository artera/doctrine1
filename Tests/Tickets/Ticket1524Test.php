<?php
namespace Tests\Tickets {

    use MyValidator;
    use Tests\DoctrineUnitTestCase;

    class Ticket1524Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $manager = \Doctrine_Manager::getInstance();
            $manager->registerValidator(MyValidator::class, 'MyValidator');
            $this->assertArrayHasKey('MyValidator', $manager->getValidators());
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
