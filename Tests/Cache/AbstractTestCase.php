<?php
namespace Tests\Cache;

use Tests\DoctrineUnitTestCase;

abstract class AbstractTestCase extends DoctrineUnitTestCase
{
    protected static array $tables = ['User'];

    public static function prepareData(): void
    {
        $user       = new \User();
        $user->name = 'Hans';
        $user->save();
    }

    public function testAsResultCache()
    {
        $this->_clearCache();
        $cache = $this->_getCacheDriver();

        static::$conn->setAttribute(\Doctrine_Core::ATTR_RESULT_CACHE, $cache);

        for ($i = 0; $i < 10; $i++) {
            $u = \Doctrine_Query::create()
                ->from('User u')
                ->addWhere('u.name = ?', ['Hans'])
                ->useResultCache($cache, 3600, 'hans_query')
                ->execute();

            $this->assertEquals(1, count($u));
            $this->assertEquals('Hans', $u[0]->name);

            if ($i == 0) {
                // Store where we're at with query count
                // as it should not increase after this initial
                // run of the loop
                $queryCount = static::$conn->count();
            }
        }

        // Query count should not have changed after first loop run
        $this->assertEquals($queryCount, static::$conn->count());
        $this->assertTrue($cache->contains('hans_query'));
    }

    public function testCacheCore()
    {
        $this->_clearCache();
        $cache = $this->_getCacheDriver();

        $object = 'test_data';
        $cache->save('foo', $object, 3600);
        $this->assertTrue($cache->contains('foo'));

        $this->assertEquals($cache->fetch('foo'), 'test_data');

        $cache->delete('foo');
        $this->assertFalse($cache->contains('foo'));
    }

    public function testDeleteByPrefix()
    {
        $this->_clearCache();
        $cache = $this->_getCacheDriver();

        $object = 'test_data';
        $cache->save('prefix_foo', $object, 3600);
        $cache->save('prefix_bar', $object, 3600);
        $cache->save('foo', $object, 3600);

        $cache->deleteByPrefix('prefix_');
        $this->assertFalse($cache->contains('prefix_foo'));
        $this->assertFalse($cache->contains('prefix_bar'));
        $this->assertTrue($cache->contains('foo'));
    }

    public function testDeleteBySuffix()
    {
        $this->_clearCache();
        $cache = $this->_getCacheDriver();

        $object = 'test_data';
        $cache->save('foo_suffix', $object, 3600);
        $cache->save('bar_suffix', $object, 3600);
        $cache->save('foo', $object, 3600);

        $cache->deleteBySuffix('_suffix');
        $this->assertFalse($cache->contains('foo_suffix'));
        $this->assertFalse($cache->contains('bar_suffix'));
        $this->assertTrue($cache->contains('foo'));
    }

    public function testDeleteByRegex()
    {
        $this->_clearCache();
        $cache = $this->_getCacheDriver();

        $object = 'test_data';
        $cache->save('foo_match_me', $object, 3600);
        $cache->save('bar_match_me', $object, 3600);
        $cache->save('foo', $object, 3600);

        $cache->deleteByRegex('/match/');
        $this->assertFalse($cache->contains('foo_match_me'));
        $this->assertFalse($cache->contains('bar_match_me'));
        $this->assertTrue($cache->contains('foo'));
    }

    abstract protected function _clearCache();
    abstract protected function _getCacheDriver();
}
