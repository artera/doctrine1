<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket428Test extends DoctrineUnitTestCase
{
    private $_albums;

    public static function prepareData(): void
    {
    }

    public function testInitData()
    {
        // Since the tests do a static::$objTable()->clear() before each method call
        // using the User model is not recommended for this test
        $albums = new \Doctrine_Collection('Album');

        $albums[0]->name           = 'Revolution';
        $albums[0]->Song[0]->title = 'Revolution';
        $albums[0]->Song[1]->title = 'Hey Jude';
        $albums[0]->Song[2]->title = 'Across the Universe';
        $albums[0]->Song[3]->title = 'Michelle';
        $albums->save();
        $this->assertEquals(count($albums[0]->Song), 4);
        $this->_albums = $albums;
    }

    public function testAggregateValueMappingSupportsLeftJoins()
    {
        foreach ($this->_albums as $album) {
            $album->clearRelated();
        }

        $q = new \Doctrine_Query();

        $q->select('a.name, COUNT(s.id) count')->from('Album a')->leftJoin('a.Song s')->groupby('a.id');
        $albums = $q->execute();

        // Collection[0] should refer to the object with aggregate value
        $this->assertEquals($albums[0]['count'], 4);
    }
}
