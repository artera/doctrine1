<?php

namespace Tests\Misc;

use Tests\DoctrineUnitTestCase;

class PessimisticLockingTest extends DoctrineUnitTestCase
{
    private static \Doctrine1\Locking\Manager\Pessimistic $lockingManager;

    /**
     * Sets up everything for the lock testing
     *
     * Creates a locking manager and a test record to work with.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$lockingManager = new \Doctrine1\Locking\Manager\Pessimistic(static::$connection);

        // Create sample data to test on
        $entry1         = new \Forum_Entry();
        $entry1->author = 'Bart Simpson';
        $entry1->topic  = 'I love donuts!';
        $entry1->save();
    }

    protected static array $tables = ['Forum_Entry', 'Entity', 'Phonenumber', 'Email', 'GroupUser'];

    /**
     * Tests the basic locking mechanism
     *
     * Currently tested: successful lock, failed lock, release lock
     */
    public function testLock()
    {
        $entries = static::$connection->query("FROM Forum_Entry WHERE Forum_Entry.author = 'Bart Simpson'");

        // Test successful lock
        $gotLock = static::$lockingManager->getLock($entries[0], 'romanb');
        $this->assertTrue($gotLock);

        // Test failed lock (another user already got a lock on the entry)
        $gotLock = static::$lockingManager->getLock($entries[0], 'konstav');
        $this->assertFalse($gotLock);

        // Test release lock
        $released = static::$lockingManager->releaseLock($entries[0], 'romanb');
        $this->assertTrue($released);
    }

    /**
     * Tests the release mechanism of aged locks
     * This test implicitly tests getLock().
     */
    public function testReleaseAgedLocks()
    {
        $entries = static::$connection->query("FROM Forum_Entry WHERE Forum_Entry.author = 'Bart Simpson'");
        static::$lockingManager->getLock($entries[0], 'romanb');
        $released = static::$lockingManager->releaseAgedLocks(-1); // age -1 seconds => release all
        $this->assertEquals(1, $released);

        // A second call should return false (no locks left)
        $released = static::$lockingManager->releaseAgedLocks(-1);
        $this->assertEquals(0, $released);

        // Test with further parameters
        static::$lockingManager->getLock($entries[0], 'romanb');
        $released = static::$lockingManager->releaseAgedLocks(-1, 'User'); // shouldnt release anything
        $this->assertEquals(0, $released);
        $released = static::$lockingManager->releaseAgedLocks(-1, 'Forum_Entry'); // should release the lock
        $this->assertEquals(1, $released);

        static::$lockingManager->getLock($entries[0], 'romanb');
        $released = static::$lockingManager->releaseAgedLocks(-1, 'Forum_Entry', 'zyne'); // shouldnt release anything
        $this->assertEquals(0, $released);
        $released = static::$lockingManager->releaseAgedLocks(-1, 'Forum_Entry', 'romanb'); // should release the lock
        $this->assertEquals(1, $released);
    }

    /**
     * Tests the retrieving of a lock's owner.
     * This test implicitly tests getLock().
     *
     * @param \Doctrine1\Record $lockedRecord
     */
    public function testGetLockOwner()
    {
        $entries = static::$connection->query("FROM Forum_Entry WHERE Forum_Entry.author = 'Bart Simpson'");
        $gotLock = static::$lockingManager->getLock($entries[0], 'romanb');
        $this->assertEquals('romanb', static::$lockingManager->getLockOwner($entries[0]));
    }
}
