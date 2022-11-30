<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC112Test extends DoctrineUnitTestCase
{
    public function testResultCacheSetHash()
    {
        $cacheDriver = new \Doctrine1\Cache\PHPArray();

        $q1 = \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'test1');

        $coll = $q1->execute();

        $this->assertTrue($cacheDriver->contains('test1'));
        $this->assertEquals(count($coll), 8);

        $coll = $q1->execute();

        $this->assertTrue($cacheDriver->contains('test1'));
        $this->assertEquals(count($coll), 8);

        $q2 = \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'test2');

        $coll = $q2->execute();
        $this->assertTrue($cacheDriver->contains('test1'));
        $this->assertTrue($cacheDriver->contains('test2'));
        $this->assertEquals(count($coll), 8);

        $q2->clearResultCache();
        $this->assertTrue($cacheDriver->contains('test1'));
        $this->assertFalse($cacheDriver->contains('test2'));

        $cacheDriver->delete('test1');
        $this->assertFalse($cacheDriver->contains('test1'));

        $q = \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver)
            ->setResultCacheHash('testing');

        $coll = $q->execute();
        $this->assertTrue($cacheDriver->contains('testing'));

        $this->assertEquals($q->getResultCacheHash(), 'testing');
        $q->setResultCacheHash(null);
        $this->assertEquals($q->getResultCacheHash(), '9b6aafa501ac37b902719cd5061f412d');
    }

    public function testDeleteByRegex()
    {
        $cacheDriver = new \Doctrine1\Cache\PHPArray(
            [
            'prefix' => 'test_'
            ]
        );

        \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'doctrine_query_one')
            ->execute();

        \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'doctrine_query_two')
            ->execute();

        $count = $cacheDriver->deleteByRegex('/test_doctrine_query_.*/');
        $this->assertEquals($count, 2);
        $this->assertFalse($cacheDriver->contains('doctrine_query_one'));
        $this->assertFalse($cacheDriver->contains('doctrine_query_two'));
    }

    public function testDeleteByPrefix()
    {
        $cacheDriver = new \Doctrine1\Cache\PHPArray(
            [
            'prefix' => 'test_'
            ]
        );

        \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'doctrine_query_one')
            ->execute();

        \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'doctrine_query_two')
            ->execute();

        $count = $cacheDriver->deleteByPrefix('test_');
        $this->assertEquals($count, 2);
        $this->assertFalse($cacheDriver->contains('doctrine_query_one'));
        $this->assertFalse($cacheDriver->contains('doctrine_query_two'));
    }

    public function testDeleteBySuffix()
    {
        $cacheDriver = new \Doctrine1\Cache\PHPArray();

        \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'one_query')
            ->execute();

        \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'two_query')
            ->execute();

        $count = $cacheDriver->deleteBySuffix('_query');
        $this->assertEquals($count, 2);
        $this->assertFalse($cacheDriver->contains('one_query'));
        $this->assertFalse($cacheDriver->contains('two_query'));
    }

    public function testDeleteWithWildcard()
    {
        $cacheDriver = new \Doctrine1\Cache\PHPArray();

        \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'user_query_one')
            ->execute();

        \Doctrine1\Query::create()
            ->from('User u')
            ->useResultCache($cacheDriver, 3600, 'user_query_two')
            ->execute();

        $count = $cacheDriver->delete('user_query_*');
        $this->assertEquals($count, 2);
        $this->assertFalse($cacheDriver->contains('user_query_one'));
        $this->assertFalse($cacheDriver->contains('user_query_two'));
    }
}
