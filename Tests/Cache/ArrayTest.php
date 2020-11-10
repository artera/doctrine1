<?php
namespace Tests\Cache;

class ArrayTest extends AbstractTestCase
{
    protected function _clearCache()
    {
        // do nothing
    }

    protected function _getCacheDriver()
    {
        return new \Doctrine_Cache_Array();
    }
}
