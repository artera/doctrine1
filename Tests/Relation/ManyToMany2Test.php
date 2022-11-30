<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class ManyToMany2Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['TestUser', 'TestMovie', 'TestMovieUserBookmark', 'TestMovieUserVote'];

    public function testManyToManyCreateSelectAndUpdate()
    {
        $user         = new \TestUser();
        $user['name'] = 'tester';
        $user->save();

        $data                      = new \TestMovie();
        $data['name']              = 'movie';
        $data['User']              = $user;
        $data['MovieBookmarks'][0] = $user;
        $data['MovieVotes'][0]     = $user;
        $data->save(); //All ok here

        static::$conn->clear();

        $q       = new \Doctrine1\Query();
        $newdata = $q->select('m.*')
            ->from('TestMovie m')
            ->execute()
            ->getFirst();
        $newdata['name'] = 'movie2';
        $newdata->save();
    }
    public function testManyToManyJoinsandSave()
    {
        $q       = new \Doctrine1\Query();
        $newdata = $q->select('d.*, i.*, u.*, c.*')
            ->from('TestMovie d, d.MovieBookmarks i, i.UserVotes u, u.User c')
            ->execute()
            ->getFirst();
        $newdata['MovieBookmarks'][0]['UserVotes'][0]['User']['name'] = 'user2';
        $newdata->save();
    }

    public function testInitMoreData()
    {
        $user       = new \TestUser();
        $user->name = 'test user';
        $user->save();

        $movie       = new \TestMovie();
        $movie->name = 'test movie';
        $movie->save();

        $movie       = new \TestMovie();
        $movie->name = 'test movie 2';
        $movie->save();

        static::$conn->clear();
    }

    public function testManyToManyDirectLinksUpdating()
    {
        $users = static::$conn->query("FROM TestUser u WHERE u.name = 'test user'");

        $this->assertEquals($users->count(), 1);

        $movies = static::$conn->query("FROM TestMovie m WHERE m.name IN ('test movie', 'test movie 2')");

        $this->assertEquals($movies->count(), 2);

        $profiler = new \Doctrine1\Connection\Profiler();

        static::$conn->addListener($profiler);

        $this->assertEquals($users[0]->UserBookmarks->count(), 0);
        $users[0]->UserBookmarks = $movies;
        $this->assertEquals($users[0]->UserBookmarks->count(), 2);

        $users[0]->save();

        $this->assertEquals($users[0]->UserBookmarks->count(), 2);
    }
}
