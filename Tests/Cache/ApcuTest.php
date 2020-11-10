<?php
namespace Tests\Cache;

/**
 * @requires extension apcu
 */
class ApcuTest extends AbstractTestCase
{
    protected function _clearCache()
    {
        apcu_clear_cache();
    }

    protected function _getCacheDriver()
    {
        return new \Doctrine_Cache_Apcu();
    }
}
