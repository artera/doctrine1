<?php
namespace Tests\Cache;

class ArrayTest extends AbstractTestCase
{
    protected function clearCache()
    {
        // do nothing
    }

    protected function getCacheDriver()
    {
        return new \Doctrine1\Cache\PHPArray();
    }
}
