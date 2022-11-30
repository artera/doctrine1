<?php
namespace Tests\Tickets {

    use MyValidator;
    use Tests\DoctrineUnitTestCase;

    class Ticket1524Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            $manager = \Doctrine1\Manager::getInstance();
            $manager->registerValidator(MyValidator::class, 'MyValidator');
            $this->assertArrayHasKey('MyValidator', $manager->getValidators());
        }
    }
}

namespace {
    class MyValidator extends \Doctrine1\Validator
    {
        public function validate($value)
        {
            return true;
        }
    }
}
