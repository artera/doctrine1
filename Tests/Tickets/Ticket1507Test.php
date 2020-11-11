<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1507Test extends DoctrineUnitTestCase
    {
        public function testInitiallyEmpty()
        {
            $c = new \Ticket1507Test_TestConfigurable();

            $this->assertEquals(null, $c->getParam('foo'));
            $this->assertEquals(null, $c->getParam('foo', 'bar'));
            $this->assertEquals(null, $c->getParams());
            $this->assertEquals(null, $c->getParams('bar'));
            $this->assertEquals([], $c->getParamNamespaces());
        }

        public function testSetGetParamWithNamespace()
        {
            $c = new \Ticket1507Test_TestConfigurable();

            $c->setParam('foo', 'bar', 'namespace');

            $this->assertEquals(['foo' => 'bar'], $c->getParams('namespace'));
            $this->assertEquals('bar', $c->getParam('foo', 'namespace'));

            $this->assertEquals(['namespace'], $c->getParamNamespaces());
        }

        public function testSetGetParamWithoutNamespace()
        {
            $c = new \Ticket1507Test_TestConfigurable();

            $c->setParam('foo', 'bar');

            $this->assertEquals(['foo' => 'bar'], $c->getParams());
            $this->assertEquals('bar', $c->getParam('foo'));

            $this->assertEquals([$c->getAttribute(\Doctrine_Core::ATTR_DEFAULT_PARAM_NAMESPACE)], $c->getParamNamespaces());
        }

        public function testSetGetParamWithNamespaceParent()
        {
            $p = new \Ticket1507Test_TestConfigurable();
            $c = new \Ticket1507Test_TestConfigurable();
            $c->setParent($p);

            $p->setParam('foo', 'bar', 'namespace');

            $this->assertEquals('bar', $c->getParam('foo', 'namespace'));
        }

        public function testSetGetParamWithoutNamespaceParent()
        {
            $p = new \Ticket1507Test_TestConfigurable();
            $c = new \Ticket1507Test_TestConfigurable();
            $c->setParent($p);

            $p->setParam('foo', 'bar');

            $this->assertEquals('bar', $c->getParam('foo'));
        }
    }
}

namespace {
    class Ticket1507Test_TestConfigurable extends Doctrine_Configurable
    {
    }
}
