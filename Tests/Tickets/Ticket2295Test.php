<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket2295Test extends DoctrineUnitTestCase
    {
        public function testMappedValueFromArray()
        {
            $test = new \Ticket2295_Record();
            $test->fromArray(['test' => 'mapped value']);
            $this->assertEquals($test->test, 'mapped value');
        }

        public function testMappedValueSynchronizeWithArray()
        {
            $test = new \Ticket2295_Record();
            $test->synchronizeWithArray(['test' => 'mapped value']);
            $this->assertEquals($test->test, 'mapped value');
        }
    }
}

namespace {
    class Ticket2295_Record extends Doctrine_Record
    {
        public function construct()
        {
            $this->mapValue('test');
        }
    }
}
