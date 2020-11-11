<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class MultiJoin2Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['QueryTest_Category', 'QueryTest_Board', 'QueryTest_User', 'QueryTest_Entry'];

    public function testInitializeData()
    {
        $query = new \Doctrine_Query(static::$connection);

        $cat = new \QueryTest_Category();

        $cat->rootCategoryId   = 0;
        $cat->parentCategoryId = 0;
        $cat->name             = 'Cat1';
        $cat->position         = 0;
        $cat->save();

        $board             = new \QueryTest_Board();
        $board->name       = 'B1';
        $board->categoryId = $cat->id;
        $board->position   = 0;
        $board->save();

        $author           = new \QueryTest_User();
        $author->username = 'romanb';
        $author->save();

        $lastEntry           = new \QueryTest_Entry();
        $lastEntry->authorId = $author->id;
        $lastEntry->date     = 1234;
        $lastEntry->save();
    }

    public function testMultipleJoinFetchingWithDeepJoins()
    {
        $query      = new \Doctrine_Query(static::$connection);
        $queryCount = static::$connection->count();
        $categories = $query->select('c.*, subCats.*, b.*, le.*, a.*')
            ->from('QueryTest_Category c')
            ->leftJoin('c.subCategories subCats')
            ->leftJoin('c.boards b')
            ->leftJoin('b.lastEntry le')
            ->leftJoin('le.author a')
            ->where('c.parentCategoryId = 0')
            ->orderBy('c.position ASC, subCats.position ASC, b.position ASC')
            ->execute();
        // Test that accessing a loaded (but empty) relation doesnt trigger an extra query
        $this->assertEquals($queryCount + 1, static::$connection->count());

        $categories[0]->subCategories;
        $this->assertEquals($queryCount + 1, static::$connection->count());
    }

    public function testMultipleJoinFetchingWithArrayFetching()
    {
        $query      = new \Doctrine_Query(static::$connection);
        $queryCount = static::$connection->count();
        $categories = $query->select('c.*, subCats.*, b.*, le.*, a.*')
            ->from('QueryTest_Category c')
            ->leftJoin('c.subCategories subCats')
            ->leftJoin('c.boards b')
            ->leftJoin('b.lastEntry le')
            ->leftJoin('le.author a')
            ->where('c.parentCategoryId = 0')
            ->orderBy('c.position ASC, subCats.position ASC, b.position ASC')
            ->execute([], \Doctrine_Core::HYDRATE_ARRAY);
    }
}
