<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket428Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testInitData()
    {
        $albums = new \Doctrine1\Collection('Album');

        $albums[0]->name           = 'Revolution';
        $albums[0]->Song[0]->title = 'Revolution';
        $albums[0]->Song[1]->title = 'Hey Jude';
        $albums[0]->Song[2]->title = 'Across the Universe';
        $albums[0]->Song[3]->title = 'Michelle';
        $albums->save();
        $this->assertEquals(count($albums[0]->Song), 4);
        return $albums;
    }

    /**
     * @depends testInitData
     */
    public function testAggregateValueMappingSupportsLeftJoins($albums)
    {
        foreach ($albums as $album) {
            $album->clearRelated();
        }

        $q = new \Doctrine1\Query();

        $q->select('a.name, COUNT(s.id) count')->from('Album a')->leftJoin('a.Song s')->groupby('a.id');
        $albums = $q->execute();

        // Collection[0] should refer to the object with aggregate value
        $this->assertEquals($albums[0]['count'], 4);
    }
}
