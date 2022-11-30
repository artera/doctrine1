<?php
class TestMovie extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasOne(
            'TestUser as User',
            ['local'               => 'user_id',
                              'foreign' => 'id']
        );

        $this->hasMany(
            'TestUser as MovieBookmarks',
            ['local'                => 'movie_id',
                              'foreign'  => 'user_id',
                              'refClass' => 'TestMovieUserBookmark']
        );

        $this->hasMany(
            'TestUser as MovieVotes',
            ['local'                => 'movie_id',
                              'foreign'  => 'user_id',
                              'refClass' => 'TestMovieUserVote']
        );
    }

    public function setTableDefinition(): void
    {
        $this->hasColumn('user_id', 'integer', null);
        $this->hasColumn('name', 'string', 30);
    }
}
