<?php

namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class OneToOneFetchingTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    public static function prepareTables(): void
    {
        static::$tables[] = 'QueryTest_Category';
        static::$tables[] = 'QueryTest_Board';
        static::$tables[] = 'QueryTest_User';
        static::$tables[] = 'QueryTest_Entry';
        static::$tables[] = 'QueryTest_Rank';
        parent::prepareTables();
    }
    public function testInitializeData()
    {
        $query = new \Doctrine1\Query(static::$connection);

        $cat = new \QueryTest_Category();

        $cat->rootCategoryId   = 0;
        $cat->parentCategoryId = 0;
        $cat->name             = 'Testcat';
        $cat->position         = 0;
        $cat->save();

        $board             = new \QueryTest_Board();
        $board->name       = 'Testboard';
        $board->categoryId = $cat->id;
        $board->position   = 0;
        $board->save();

        $author           = new \QueryTest_User();
        $author->username = 'romanbb';
        $author->save();

        $lastEntry           = new \QueryTest_Entry();
        $lastEntry->authorId = $author->id;
        $lastEntry->date     = 1234;
        $lastEntry->save();

        // Set the last entry
        $board->lastEntry = $lastEntry;
        $board->save();

        $visibleRank        = new \QueryTest_Rank();
        $visibleRank->title = 'Freak';
        $visibleRank->color = 'red';
        $visibleRank->icon  = 'freak.png';
        $visibleRank->save();

        // grant him a rank
        $author->visibleRank = $visibleRank;
        $author->save();
    }

    /**
     * Tests that one-one relations are correctly loaded with array fetching
     * when the related records EXIST.
     *
     * !!! Currently it produces a notice with:
     * !!! Array to string conversion in Doctrine1\Hydrate.php on line 937
     *
     * !!! And shortly after exits with a fatal error:
     * !!! Fatal error:  Cannot create references to/from string offsets nor overloaded objects
     * !!! in Doctrine1\Hydrate.php on line 939
     */
    public function testOneToOneArrayFetchingWithExistingRelations()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $categories = $query->select('c.*, b.*, le.*, a.username, vr.title, vr.color, vr.icon')
                    ->from('QueryTest_Category c')
                    ->leftJoin('c.boards b')
                    ->leftJoin('b.lastEntry le')
                    ->leftJoin('le.author a')
                    ->leftJoin('a.visibleRank vr')
                    ->execute([], \Doctrine1\HydrationMode::Array);

        // --> currently quits here with a fatal error! <--

        // check boards/categories
        $this->assertEquals(1, count($categories));
        $this->assertTrue(isset($categories[0]['boards']));
        $this->assertEquals(1, count($categories[0]['boards']));

        // get the baord for inspection
        $board = $categories[0]['boards'][0];

        $this->assertTrue(isset($board['lastEntry']));

        // lastentry should've 2 fields. one regular field, one relation.
        //$this->assertEquals(2, count($board['lastEntry']));
        $this->assertEquals(1234, (int)$board['lastEntry']['date']);
        $this->assertTrue(isset($board['lastEntry']['author']));

        // author should've 2 fields. one regular field, one relation.
        //$this->assertEquals(2, count($board['lastEntry']['author']));
        $this->assertEquals('romanbb', $board['lastEntry']['author']['username']);
        $this->assertTrue(isset($board['lastEntry']['author']['visibleRank']));

        // visibleRank should've 3 regular fields
        //$this->assertEquals(3, count($board['lastEntry']['author']['visibleRank']));
        $this->assertEquals('Freak', $board['lastEntry']['author']['visibleRank']['title']);
        $this->assertEquals('red', $board['lastEntry']['author']['visibleRank']['color']);
        $this->assertEquals('freak.png', $board['lastEntry']['author']['visibleRank']['icon']);
    }

    /**
    * Tests that one-one relations are correctly loaded with array fetching
    * when the related records DONT EXIST.
    */
    public function testOneToOneArrayFetchingWithEmptyRelations()
    {
        // temporarily remove the relation to fake a non-existant one
        $board              = static::$connection->query('FROM QueryTest_Board b WHERE b.name = ?', ['Testboard'])->getFirst();
        $lastEntryId        = $board->lastEntryId;
        $board->lastEntryId = 0;
        $board->save();

        $query = new \Doctrine1\Query(static::$connection);
        $categories = $query->select('c.*, b.*, le.*, a.username, vr.title, vr.color, vr.icon')
                    ->from('QueryTest_Category c')
                    ->leftJoin('c.boards b')
                    ->leftJoin('b.lastEntry le')
                    ->leftJoin('le.author a')
                    ->leftJoin('a.visibleRank vr')
                    ->execute([], \Doctrine1\HydrationMode::Array);


        // check boards/categories
        $this->assertEquals(1, count($categories));
        $this->assertTrue(isset($categories[0]['boards']));
        $this->assertEquals(1, count($categories[0]['boards']));

        // get the board for inspection
        $tmpBoard = $categories[0]['boards'][0];

        $this->assertTrue(!isset($tmpBoard['lastEntry']));

        $board->lastEntryId = $lastEntryId;
        $board->save();
    }

    // Tests that one-one relations are correctly loaded with record fetching
    // when the related records EXIST.
    public function testOneToOneRecordFetchingWithExistingRelations()
    {
        $query = new \Doctrine1\Query(static::$connection);
        $categories = $query->select('c.*, b.*, le.date, a.username, vr.title, vr.color, vr.icon')
                    ->from('QueryTest_Category c')
                    ->leftJoin('c.boards b')
                    ->leftJoin('b.lastEntry le')
                    ->leftJoin('le.author a')
                    ->leftJoin('a.visibleRank vr')
                    ->execute();

        // check boards/categories
        $this->assertEquals(1, count($categories));
        $this->assertEquals(1, count($categories[0]['boards']));

        // get the baord for inspection
        $board = $categories[0]['boards'][0];

        $this->assertEquals(1234, (int)$board['lastEntry']['date']);
        $this->assertTrue(isset($board['lastEntry']['author']));

        $this->assertEquals('romanbb', $board['lastEntry']['author']['username']);
        $this->assertTrue(isset($board['lastEntry']['author']['visibleRank']));

        $this->assertEquals('Freak', $board['lastEntry']['author']['visibleRank']['title']);
        $this->assertEquals('red', $board['lastEntry']['author']['visibleRank']['color']);
        $this->assertEquals('freak.png', $board['lastEntry']['author']['visibleRank']['icon']);
    }


    // Tests that one-one relations are correctly loaded with record fetching
    // when the related records DONT EXIST.

    public function testOneToOneRecordFetchingWithEmptyRelations()
    {
        // temporarily remove the relation to fake a non-existant one
        $board              = static::$connection->query('FROM QueryTest_Board b WHERE b.name = ?', ['Testboard'])->getFirst();
        $lastEntryId        = $board->lastEntryId;
        $board->lastEntryId = null;
        $board->lastEntry   = null;
        $board->save();

        $query = new \Doctrine1\Query(static::$connection);
        $categories = $query->select('c.*, b.*, le.*, a.username, vr.title, vr.color, vr.icon')
                ->from('QueryTest_Category c')
                ->leftJoin('c.boards b')
                ->leftJoin('b.lastEntry le')
                ->leftJoin('le.author a')
                ->leftJoin('a.visibleRank vr')
                ->execute();

        // check boards/categories
        $this->assertEquals(1, count($categories));
        $this->assertTrue(isset($categories[0]['boards']));
        $this->assertEquals(1, count($categories[0]['boards']));

        // get the board for inspection
        $tmpBoard = $categories[0]['boards'][0];
        $this->assertFalse(isset($tmpBoard['lastEntry']));

        $board->lastEntryId = $lastEntryId;
        //$board->save();
    }
}
