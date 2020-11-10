<?php
namespace Tests\Cache;

/**
 * @requires extension xcache
 */
class XcacheTest extends AbstractTestCase
{
    protected function _clearCache()
    {
        for ($i = 0, $count = xcache_count(XC_TYPE_VAR); $i < $count; $i++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }
    }

    protected function _getCacheDriver()
    {
        return new \Doctrine_Cache_Xcache();
    }
}
