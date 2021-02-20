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

        public function testCustomHydratorUsingClassInstanceExceptingException()
        {
            $this->expectException(\TypeError::class);

            $hydrator = new \StdClass();
            \Doctrine_Manager::getInstance()
                ->registerHydrator('MyHydrator', $hydrator);
        }
    }
}

namespace {
    class MyHydrator extends Doctrine_Hydrator_Abstract
    {
        protected array $_queryComponents;
        protected array $_tableAliases;
        protected int|string $_hydrationMode;

        public function hydrateResultSet(Doctrine_Connection_Statement $stmt): string
        {
            return 'MY_HYDRATOR';
        }
    }
}
