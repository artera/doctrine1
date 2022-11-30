<?php
namespace Tests\Cache;

class DbTest extends AbstractTestCase
{
    protected $cache;

    public function setUp(): void
    {
        parent::setUp();

        $this->cache = new \Doctrine1\Cache\Db([
            'connection' => static::$connection,
            'tableName'  => 'd_cache',
        ]);
        static::$connection->exec('DROP TABLE IF EXISTS d_cache');
        $this->cache->createTable();
    }

    protected function clearCache()
    {
        static::$connection->exec('DELETE FROM d_cache');
    }

    protected function getCacheDriver()
    {
        return $this->cache;
    }

    public function testAsResultCache()
    {
        $this->clearCache();
        $cache = $this->getCacheDriver();

        static::$conn->setAttribute(\Doctrine1\Core::ATTR_RESULT_CACHE, $cache);

        $queryCountBefore = static::$conn->count();

        for ($i = 0; $i < 10; $i++) {
            $u = \Doctrine1\Query::create()
                ->from('User u')
                ->addWhere('u.name = ?', ['Hans'])
                ->useResultCache($cache, 3600, 'hans_query')
                ->execute();
            $this->assertEquals(1, count($u));
            $this->assertEquals('Hans', $u[0]->name);
        }

        $this->assertTrue($cache->contains('hans_query'));
    }
}
