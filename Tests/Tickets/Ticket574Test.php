<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket574Test extends DoctrineUnitTestCase
{
    /**
     * prepareData
     */
    public static function prepareData(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $oAuthor          = new \Author;
            $oAuthor->book_id = $i;
            $oAuthor->name    = "Author $i";
            $oAuthor->save();
        }
    }

    /**
     * prepareTables
     */

    public static function prepareTables(): void
    {
        static::$tables   = [];
        static::$tables[] = 'Author';
        static::$tables[] = 'Book';

        parent :: prepareTables();
    }


    /**
     * Test the existence expected indexes
     */

    public function testTicket()
    {
        $q = new \Doctrine_Query();

        // simple query with 1 column selected
        $cAuthors = $q->select('book_id')->from('Author')->groupBy('book_id')->where('book_id = 2')->execute();

        // simple query, with 1 join and all columns selected
        $cAuthors = $q->from('Author, Author.Book')->execute();

        foreach ($cAuthors as $oAuthor) {
            if (! $oAuthor->name) {
                $this->fail('Querying the same table multiple times triggers hydration/caching(?) bug');
            }
        }
    }
}
