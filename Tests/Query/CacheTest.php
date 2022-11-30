<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class CacheTest extends DoctrineUnitTestCase
{
    public function testQueryCacheAddsQueryIntoCache()
    {
        $cache = $this->getCacheDriver();

        $q = \Doctrine1\Query::create()
            ->select('u.id, u.name, p.id')
            ->from('User u')
            ->leftJoin('u.Phonenumber p')
            ->where('u.name = ?', 'walhala')
            ->useQueryCache($cache);

        $coll = $q->execute();

        $this->assertTrue($cache->contains($q->calculateQueryCacheHash()));
        $this->assertEquals(count($coll), 0);

        $coll = $q->execute();

        $this->assertTrue($cache->contains($q->calculateQueryCacheHash()));
        $this->assertEquals(count($coll), 0);
    }

    public function testQueryCacheWorksWithGlobalConfiguration()
    {
        $cache = $this->getCacheDriver();

        \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_QUERY_CACHE, $cache);

        $q = \Doctrine1\Query::create()
            ->select('u.id, u.name, p.id')
            ->from('User u')
            ->leftJoin('u.Phonenumber p');

        $coll = $q->execute();

        $this->assertTrue($cache->contains($q->calculateQueryCacheHash()));
        $this->assertEquals(count($coll), 8);

        $coll = $q->execute();

        $this->assertTrue($cache->contains($q->calculateQueryCacheHash()));
        $this->assertEquals(count($coll), 8);
    }

    public function testResultSetCacheAddsResultSetsIntoCache()
    {
        $q = new \Doctrine1\Query();

        $cache = $this->getCacheDriver();
        $q->useResultCache($cache)->select('u.name')->from('User u');
        $coll = $q->execute();

        $this->assertTrue($cache->contains($q->calculateResultCacheHash()));
        $this->assertEquals(count($coll), 8);

        $coll = $q->execute();

        $this->assertTrue($cache->contains($q->calculateResultCacheHash()));
        $this->assertEquals(count($coll), 8);
    }

    public function testResultSetCacheSupportsQueriesWithJoins()
    {
        $q = new \Doctrine1\Query();

        $cache = $this->getCacheDriver();
        $q->useResultCache($cache);
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p');
        $coll = $q->execute();

        $this->assertTrue($cache->contains($q->calculateResultCacheHash()));
        $this->assertEquals(count($coll), 8);

        $coll = $q->execute();

        $this->assertTrue($cache->contains($q->calculateResultCacheHash()));
        $this->assertEquals(count($coll), 8);
    }

    public function testResultSetCacheSupportsPreparedStatements()
    {
        $q = new \Doctrine1\Query();

        $cache = $this->getCacheDriver();
        $q->useResultCache($cache);
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p')
            ->where('u.id = ?');

        $coll = $q->execute([5]);

        $this->assertTrue($coll instanceof \Doctrine1\Collection);
        $this->assertEquals(5, $coll[0]->id);
        $this->assertTrue($coll[0] instanceof \Doctrine1\Record);
        $this->assertTrue($coll[0]->Phonenumber[0] instanceof \Doctrine1\Record);
        $this->assertTrue($cache->contains($q->calculateResultCacheHash([5])));
        $this->assertEquals(count($coll), 1);
        $coll->free(true);

        $coll = $q->execute([5]);

        $this->assertTrue($coll instanceof \Doctrine1\Collection);
        $this->assertEquals(5, $coll[0]->id);
        $this->assertTrue($coll[0] instanceof \Doctrine1\Record);
        // references to related objects are not serialized/unserialized, so the following
        // would trigger an additional query (lazy-load).
        //echo static::$conn->count() . "<br/>";
        //$this->assertTrue($coll[0]->Phonenumber[0] instanceof \Doctrine1\Record);
        //echo static::$conn->count() . "<br/>"; // count is increased => lazy load
        $this->assertTrue($cache->contains($q->calculateResultCacheHash([5])));
        $this->assertEquals(count($coll), 1);
    }

    public function testUseCacheSupportsBooleanTrueAsParameter()
    {
        $q = new \Doctrine1\Query();

        $cache = $this->getCacheDriver();
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_CACHE, $cache);

        $q->useResultCache(true);
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p')
            ->where('u.id = ?');

        $coll = $q->execute([5]);

        $this->assertTrue($cache->contains($q->calculateResultCacheHash([5])));
        $this->assertEquals(count($coll), 1);

        $coll = $q->execute([5]);

        $this->assertTrue($cache->contains($q->calculateResultCacheHash([5])));
        $this->assertEquals(count($coll), 1);

        static::$conn->setAttribute(\Doctrine1\Core::ATTR_CACHE, null);
    }

    public function testResultCacheLifeSpan()
    {
        // initially NULL = not cached
        $q = new \Doctrine1\Query();
        $this->assertSame(null, $q->getResultCacheLifeSpan());
        $q->free();

        // 0 = cache forever
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_RESULT_CACHE_LIFESPAN, 0);
        $q = new \Doctrine1\Query();
        $this->assertSame(0, $q->getResultCacheLifeSpan());
        $q->free();

        static::$manager->setAttribute(\Doctrine1\Core::ATTR_RESULT_CACHE_LIFESPAN, 3600);
        $q = new \Doctrine1\Query();
        $this->assertSame(3600, $q->getResultCacheLifeSpan());
        $q->free();

        // test that value set on connection level has precedence
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_RESULT_CACHE_LIFESPAN, 42);
        $q = new \Doctrine1\Query();
        $this->assertSame(42, $q->getResultCacheLifeSpan());
        $q->free();

        // test that value set on the query has highest precedence
        $q = new \Doctrine1\Query();
        $q->useResultCache(true, 1234);
        $this->assertSame(1234, $q->getResultCacheLifeSpan());
        $q->setResultCacheLifeSPan(4321);
        $this->assertSame(4321, $q->getResultCacheLifeSpan());
        $q->free();
    }

    public function testQueryCacheLifeSpan()
    {
        // initially NULL = not cached
        $q = new \Doctrine1\Query();
        $this->assertSame(null, $q->getQueryCacheLifeSpan());
        $q->free();

        // 0 = forever
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_QUERY_CACHE_LIFESPAN, 0);
        $q = new \Doctrine1\Query();
        $this->assertSame(0, $q->getQueryCacheLifeSpan());
        $q->free();

        static::$manager->setAttribute(\Doctrine1\Core::ATTR_QUERY_CACHE_LIFESPAN, 3600);
        $q = new \Doctrine1\Query();
        $this->assertSame(3600, $q->getQueryCacheLifeSpan());
        $q->free();

        // test that value set on connection level has precedence
        static::$conn->setAttribute(\Doctrine1\Core::ATTR_QUERY_CACHE_LIFESPAN, 42);
        $q = new \Doctrine1\Query();
        $this->assertSame(42, $q->getQueryCacheLifeSpan());
        $q->free();

        // test that value set on the query has highest precedence
        $q = new \Doctrine1\Query();
        $q->setQueryCacheLifeSpan(4321);
        $this->assertSame(4321, $q->getQueryCacheLifeSpan());
        $q->free();
    }

    public function testQueryCacheCanBeDisabledForSingleQuery()
    {
        $cache = $this->getCacheDriver();
        $q     = new \Doctrine1\Query();
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p')->where('u.name = ?', 'walhala')
            ->useQueryCache(false);

        $coll = $q->execute();

        $this->assertFalse($cache->contains($q->calculateQueryCacheHash()));
        $this->assertEquals(count($coll), 0);

        $coll = $q->execute();

        $this->assertFalse($cache->contains($q->calculateQueryCacheHash()));
        $this->assertEquals(count($coll), 0);
    }

    protected function getCacheDriver()
    {
        return new \Doctrine1\Cache\PHPArray();
    }
}
