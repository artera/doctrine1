<?php
namespace Tests\Cache;

/**
 * @requires extension apcu
 */
class ApcuTest extends AbstractTestCase
{
    protected function clearCache()
    {
        apcu_clear_cache();
    }

    protected function getCacheDriver()
    {
        return new \Doctrine1\Cache\Apcu();
    }
}
