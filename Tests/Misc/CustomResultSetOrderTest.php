<?php
namespace Tests\Misc;

use Tests\DoctrineUnitTestCase;

class CustomResultSetOrderTest extends DoctrineUnitTestCase
{

    /**
     * Prepares the data under test.
     *
     * 1st category: 3 Boards
     * 2nd category: 1 Board
     * 3rd category: 0 boards
     *
     */
    public static function prepareData(): void
    {
        static::$connection->clear();
        $cat1           = new \CategoryWithPosition();
        $cat1->position = 0;
        $cat1->name     = 'First';

        $cat2           = new \CategoryWithPosition();
        $cat2->position = 0; // same 'priority' as the first
        $cat2->name     = 'Second';

        $cat3           = new \CategoryWithPosition();
        $cat3->position = 1;
        $cat3->name     = 'Third';

        $board1           = new \BoardWithPosition();
        $board1->position = 0;

        $board2           = new \BoardWithPosition();
        $board2->position = 1;

        $board3           = new \BoardWithPosition();
        $board3->position = 2;

        // The first category gets 3 boards!
        $cat1->Boards[0] = $board1;
        $cat1->Boards[1] = $board2;
        $cat1->Boards[2] = $board3;

        $board4           = new \BoardWithPosition();
        $board4->position = 0;

        // The second category gets 1 board!
        $cat2->Boards[0] = $board4;

        static::$connection->flush();
    }

    /**
     * Prepares the tables.
     */
    public static function prepareTables(): void
    {
        static::$tables[] = 'CategoryWithPosition';
        static::$tables[] = 'BoardWithPosition';
        parent::prepareTables();
    }

    /**
     * Checks whether the boards are correctly assigned to the categories.
     *
     * The 'evil' result set that confuses the object population is displayed below.
     *
     * catId | catPos | catName  | boardPos | board.category_id
     *  1    | 0      | First    | 0        | 1
     *  2    | 0      | Second   | 0        | 2   <-- The split that confuses the object population
     *  1    | 0      | First    | 1        | 1
     *  1    | 0      | First    | 2        | 1
     *  3    | 2      | Third    | NULL
     */
    public function testQueryWithOrdering2()
    {
        $q = new \Doctrine1\Query(static::$connection);

        $categories = $q->select('c.*, b.*')
                ->from('CategoryWithPosition c')
                ->leftJoin('c.Boards b')
                ->orderBy('c.position ASC, b.position ASC')
                ->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $this->assertEquals(3, count($categories), 'Some categories were doubled!');

        // Check each category
        foreach ($categories as $category) {
            switch ($category['name']) {
                case 'First':
                    // The first category should have 3 boards, right?
                    // It has only 1! The other two slipped to the 2nd category!
                    $this->assertEquals(3, count($category['Boards']));
                    break;
                case 'Second':
                    // The second category should have 1 board, but it got 3 now
                    $this->assertEquals(1, count($category['Boards']));
                    ;
                    break;
                case 'Third':
                    // The third has no boards as expected.
                    //print $category->Boards[0]->position;
                    $this->assertEquals(0, count($category['Boards']));
                    break;
            }
        }
    }

    /**
     * Checks whether the boards are correctly assigned to the categories.
     *
     * The 'evil' result set that confuses the object population is displayed below.
     *
     * catId | catPos | catName  | boardPos | board.category_id
     *  1    | 0      | First    | 0        | 1
     *  2    | 0      | Second   | 0        | 2   <-- The split that confuses the object population
     *  1    | 0      | First    | 1        | 1
     *  1    | 0      | First    | 2        | 1
     *  3    | 2      | Third    | NULL
     */
    public function testQueryWithOrdering()
    {
        $q          = new \Doctrine1\Query(static::$connection);
        $categories = $q->select('c.*, b.*')
                ->from('CategoryWithPosition c')
                ->leftJoin('c.Boards b')
                ->orderBy('c.position ASC, b.position ASC')
                ->execute();

        $this->assertEquals(3, $categories->count(), 'Some categories were doubled!');

        // Check each category
        foreach ($categories as $category) {
            switch ($category->name) {
                case 'First':
                    // The first category should have 3 boards

                    $this->assertEquals(3, $category->Boards->count());
                    break;
                case 'Second':
                    // The second category should have 1 board

                    $this->assertEquals(1, $category->Boards->count());
                    break;
                case 'Third':
                    // The third has no boards as expected.
                    //print $category->Boards[0]->position;
                    $this->assertEquals(0, $category->Boards->count());
                    break;
            }
        }
    }
}
