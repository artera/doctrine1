<?php

namespace Tests\Core\Hydrate {
    use Tests\DoctrineUnitTestCase;

    class DriverTest extends DoctrineUnitTestCase
    {
        public function testCustomHydrator()
        {
            \Doctrine1\Manager::getInstance()
                ->registerHydrator('MyHydrator', 'MyHydrator');

            $result = \Doctrine1\Core::getTable('User')
                ->createQuery('u')
                ->execute([], 'MyHydrator');

            $this->assertEquals($result, 'MY_HYDRATOR');
        }

        public function testCustomHydratorUsingClassInstance()
        {
            $hydrator = new \MyHydrator();
            \Doctrine1\Manager::getInstance()
                ->registerHydrator('MyHydrator', $hydrator);

            $result = \Doctrine1\Core::getTable('User')
                ->createQuery('u')
                ->execute([], 'MyHydrator');

            $this->assertEquals($result, 'MY_HYDRATOR');
        }

        public function testCustomHydratorUsingClassInstanceExceptingException()
        {
            $this->expectException(\TypeError::class);

            $hydrator = new \StdClass();
            \Doctrine1\Manager::getInstance()
                ->registerHydrator('MyHydrator', $hydrator);
        }
    }
}

namespace {
    use Doctrine1\HydrationMode;

    class MyHydrator extends \Doctrine1\Hydrator\AbstractHydrator
    {
        protected array $queryComponents;
        protected array $tableAliases;
        protected HydrationMode|string $hydrationMode;

        public function hydrateResultSet(\Doctrine1\Connection\Statement $stmt): string
        {
            return 'MY_HYDRATOR';
        }
    }
}
