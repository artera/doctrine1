<?php
namespace Tests\Core\Hydrate {
    use Tests\DoctrineUnitTestCase;

    class DriverTest extends DoctrineUnitTestCase
    {
        public function testCustomHydrator()
        {
            \Doctrine_Manager::getInstance()
                ->registerHydrator('MyHydrator', 'MyHydrator');

            $result = \Doctrine_Core::getTable('User')
                ->createQuery('u')
                ->execute([], 'MyHydrator');

            $this->assertEquals($result, 'MY_HYDRATOR');
        }

        public function testCustomHydratorUsingClassInstance()
        {
            $hydrator = new \MyHydrator();
            \Doctrine_Manager::getInstance()
                ->registerHydrator('MyHydrator', $hydrator);

            $result = \Doctrine_Core::getTable('User')
                ->createQuery('u')
                ->execute([], 'MyHydrator');

            $this->assertEquals($result, 'MY_HYDRATOR');
        }

        public function testCustomHydratorConstructor()
        {
            $queryComponents = ['queryComponents'];
            $tableAliases    = ['tableAliases'];
            $hydrationMode   = ['hydrationMode'];

            $hydrator = new \MyHydrator($queryComponents, $tableAliases, $hydrationMode);

            $this->assertEquals($queryComponents, $hydrator->_queryComponents);
            $this->assertEquals($tableAliases, $hydrator->_tableAliases);
            $this->assertEquals($hydrationMode, $hydrator->_hydrationMode);
        }

        public function testCustomHydratorUsingClassInstanceExceptingException()
        {
            $hydrator = new \StdClass();
            \Doctrine_Manager::getInstance()
                ->registerHydrator('MyHydrator', $hydrator);

            $this->expectException(\Doctrine_Hydrator_Exception::class);
            \Doctrine_Core::getTable('User')
                ->createQuery('u')
                ->execute([], 'MyHydrator');
        }
    }
}

namespace {
    class MyHydrator extends Doctrine_Hydrator_Abstract
    {
        public $_queryComponents;
        public $_tableAliases;
        public $_hydrationMode;

        public function hydrateResultSet($stmt)
        {
            return 'MY_HYDRATOR';
        }
    }
}
