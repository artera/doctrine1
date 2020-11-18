<?php
namespace Tests\EventListener;

use Tests\DoctrineUnitTestCase;

class ChainTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['EventListenerChainTest'];

    public function testAccessorInvokerChain()
    {
        $e       = new \EventListenerChainTest;
        $e->name = 'something';
    }
}
