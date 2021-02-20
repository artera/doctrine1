<?php
class TestUser extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'TestMovie as UserBookmarks',
            ['local'                => 'user_id',
                              'foreign'  => 'movie_id',
                              'refClass' => 'TestMovieUserBookmark']
        );

        $this->hasMany(
            'TestMovie as UserVotes',
            ['local'                => 'user_id',
                              'foreign'  => 'movie_id',
                              'refClass' => 'TestMovieUserVote']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 30);
    }
}
