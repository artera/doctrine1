<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket587Test extends DoctrineUnitTestCase
{
    protected static array $tables = ['BookmarkUser', 'Bookmark', 'Page'];

    public static function prepareData(): void
    {
    }

    public function testInit()
    {
        $user         = new \BookmarkUser();
        $user['name'] = 'Anonymous';
        $user->save();

        $pages            = new \Doctrine_Collection('Page');
        $pages[0]['name'] = 'Yahoo';
        $pages[0]['url']  = 'http://www.yahoo.com';
        $pages->save();

        $this->assertEquals(count($pages), 1);
    }

    /**
     * This test case demonstrates an issue with the identity case in the
     * Doctrine_Table class.  The brief summary is that if you create a
     * record, then delete it, then create another record with the same
     * primary keys, the record can get into a state where it is in the
     * database but may appear to be marked as TCLEAN under certain
     * circumstances (such as when it comes back as part of a collection).
     * This makes the $record->exists() method return false, which prevents
     * the record from being deleted among other things.
     */
    public function testIdentityMapAndRecordStatus()
    {
        // load our user and our collection of pages
        $user = \Doctrine_Query::create()->query(
            'SELECT * FROM BookmarkUser u WHERE u.name=?',
            ['Anonymous']
        )->getFirst();
        $pages = \Doctrine_Query::create()->query('SELECT * FROM Page');

        // bookmark the pages (manually)
        foreach ($pages as $page) {
            $bookmark            = new \Bookmark();
            $bookmark['page_id'] = $page['id'];
            $bookmark['user_id'] = $user['id'];
            $bookmark->save();
        }

        // select all bookmarks
        $bookmarks = \Doctrine_Manager::connection()->query(
            'SELECT * FROM Bookmark b'
        );
        $this->assertEquals(count($bookmarks), 1);

        // verify that they all exist
        foreach ($bookmarks as $bookmark) {
            $this->assertTrue($bookmark->exists());
        }

        // now delete them all.
        $user['Bookmarks']->delete();

        // verify count when accessed directly from database
        $bookmarks = \Doctrine_Query::create()->query(
            'SELECT * FROM Bookmark'
        );
        $this->assertEquals(count($bookmarks), 0);

        // now recreate bookmarks and verify they exist:
        foreach ($pages as $page) {
            $bookmark            = new \Bookmark();
            $bookmark['page_id'] = $page['id'];
            $bookmark['user_id'] = $user['id'];
            $bookmark->save();
        }

        // select all bookmarks for the user
        $bookmarks = \Doctrine_Manager::connection()->query(
            'SELECT * FROM Bookmark b'
        );
        $this->assertEquals(count($bookmarks), 1);

        // verify that they all exist
        foreach ($bookmarks as $bookmark) {
            $this->assertTrue($bookmark->exists());
        }
    }
}
