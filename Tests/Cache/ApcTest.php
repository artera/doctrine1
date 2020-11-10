<?php
namespace Tests\Cache;

/**
 * @requires extension apc
 */
class ApcTest extends AbstractTestCase
{
    protected function _clearCache()
    {
        apc_clear_cache('user');
    }

    protected function _getCacheDriver()
    {
        return new \Doctrine_Cache_Apc();
    }
}
