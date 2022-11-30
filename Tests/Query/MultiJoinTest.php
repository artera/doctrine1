<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class MultiJoinTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['Book', 'Author', 'Album', 'User'];

    public function setUp(): void
    {
    }

    public function testInitializeData()
    {
        $query = new \Doctrine1\Query(static::$connection);

        $user = static::$connection->getTable('User')->find(4);


        $album = static::$connection->create('Album');
        $album->Song[0];

        $user->Album[0]->name = 'Damage Done';
        $user->Album[1]->name = 'Haven';

        $user->Album[0]->Song[0]->title = 'Damage Done';
        $user->Album[0]->Song[1]->title = 'The Treason Wall';
        $user->Album[0]->Song[2]->title = 'Monochromatic Stains';

        $this->assertEquals(count($user->Album[0]->Song), 3);


        $user->Album[1]->Song[0]->title = 'Not Built To Last';
        $user->Album[1]->Song[1]->title = 'The Wonders At Your Feet';
        $user->Album[1]->Song[2]->title = 'Feast Of Burden';
        $user->Album[1]->Song[3]->title = 'Fabric';
        $this->assertEquals(count($user->Album[1]->Song), 4);

        $user->save();

        $user = static::$connection->getTable('User')->find(4);

        $this->assertEquals(count($user->Album[0]->Song), 3);
        $this->assertEquals(count($user->Album[1]->Song), 4);


        $user = static::$connection->getTable('User')->find(5);

        $user->Album[0]->name           = 'Clayman';
        $user->Album[1]->name           = 'Colony';
        $user->Album[1]->Song[0]->title = 'Colony';
        $user->Album[1]->Song[1]->title = 'Ordinary Story';

        $user->save();

        $this->assertEquals(count($user->Album[0]->Song), 0);
        $this->assertEquals(count($user->Album[1]->Song), 2);
    }

    /**
     * @depends testInitializeData
     */
    public function testMultipleOneToManyFetching()
    {
        static::$connection->clear();

        $query = new \Doctrine1\Query();

        $users = $query->query('FROM User.Album.Song, User.Phonenumber WHERE User.id IN (4,5) ORDER BY User.id, User.Album.name, User.Album.Song.title');

        $this->assertEquals($users->count(), 2);

        $this->assertEquals($users[0]->id, 4);

        $this->assertEquals($users[0]->Album[0]->name, 'Damage Done');
        $this->assertEquals($users[0]->Album[0]->Song[0]->title, 'Damage Done');
        $this->assertEquals($users[0]->Album[0]->Song[1]->title, 'Monochromatic Stains');
        $this->assertEquals($users[0]->Album[0]->Song[2]->title, 'The Treason Wall');
        $this->assertEquals($users[0]->Album[1]->name, 'Haven');
        $this->assertEquals($users[0]->Album[1]->Song[0]->title, 'Fabric');
        $this->assertEquals($users[0]->Album[1]->Song[1]->title, 'Feast Of Burden');
        $this->assertEquals($users[0]->Album[1]->Song[2]->title, 'Not Built To Last');
        $this->assertEquals($users[0]->Album[1]->Song[3]->title, 'The Wonders At Your Feet');

        $this->assertEquals($users[1]->id, 5);
        $this->assertEquals($users[1]->Album[0]->name, 'Clayman');
        $this->assertEquals($users[1]->Album[1]->name, 'Colony');
        $this->assertEquals($users[1]->Album[1]->Song[0]->title, 'Colony');
        $this->assertEquals($users[1]->Album[1]->Song[1]->title, 'Ordinary Story');

        $this->assertEquals($users[0]->Phonenumber[0]->phonenumber, '123 123');

        $this->assertEquals($users[1]->Phonenumber[0]->phonenumber, '123 123');
        $this->assertEquals($users[1]->Phonenumber[1]->phonenumber, '456 456');
        $this->assertEquals($users[1]->Phonenumber[2]->phonenumber, '789 789');
    }

    /**
     * @depends testInitializeData
     */
    public function testInitializeMoreData()
    {
        $user                           = static::$connection->getTable('User')->find(4);
        $user->Book[0]->name            = 'The Prince';
        $user->Book[0]->Author[0]->name = 'Niccolo Machiavelli';
        $user->Book[0]->Author[1]->name = 'Someone';
        $user->Book[1]->name            = 'The Art of War';
        $user->Book[1]->Author[0]->name = 'Someone';
        $user->Book[1]->Author[1]->name = 'Niccolo Machiavelli';


        $user->save();

        $user                           = static::$connection->getTable('User')->find(5);
        $user->Book[0]->name            = 'Zadig';
        $user->Book[0]->Author[0]->name = 'Voltaire';
        $user->Book[0]->Author[1]->name = 'Someone';
        $user->Book[1]->name            = 'Candide';
        $user->Book[1]->Author[0]->name = 'Someone';
        $user->Book[1]->Author[1]->name = 'Voltaire';
        $user->save();

        static::$connection->clear();
    }

    /**
     * @depends testInitializeData
     */
    public function testMultipleOneToManyFetching2()
    {
        $query = new \Doctrine1\Query();

        $users = $query->query('FROM User.Album.Song, User.Book.Author WHERE User.id IN (4,5) ORDER BY User.id, User.Album.name, User.Album.Song.title, User.Book.name, User.Book.Author.name');

        $this->assertEquals($users->count(), 2);

        $this->assertEquals($users[0]->id, 4);
        $this->assertEquals($users[0]->Album[0]->name, 'Damage Done');
        $this->assertEquals($users[0]->Album[0]->Song[0]->title, 'Damage Done');
        $this->assertEquals($users[0]->Album[0]->Song[1]->title, 'Monochromatic Stains');
        $this->assertEquals($users[0]->Album[0]->Song[2]->title, 'The Treason Wall');
        $this->assertEquals($users[0]->Album[1]->name, 'Haven');
        $this->assertEquals($users[0]->Album[1]->Song[0]->title, 'Fabric');
        $this->assertEquals($users[0]->Album[1]->Song[1]->title, 'Feast Of Burden');
        $this->assertEquals($users[0]->Album[1]->Song[2]->title, 'Not Built To Last');
        $this->assertEquals($users[0]->Album[1]->Song[3]->title, 'The Wonders At Your Feet');

        $this->assertEquals($users[0]->Book[0]->name, 'The Art of War');
        $this->assertEquals($users[0]->Book[0]->Author[0]->name, 'Niccolo Machiavelli');
        $this->assertEquals($users[0]->Book[0]->Author[1]->name, 'Someone');
        $this->assertEquals($users[0]->Book[1]->name, 'The Prince');
        $this->assertEquals($users[0]->Book[1]->Author[0]->name, 'Niccolo Machiavelli');
        $this->assertEquals($users[0]->Book[1]->Author[1]->name, 'Someone');

        $this->assertEquals($users[1]->id, 5);
        $this->assertEquals($users[1]->Album[0]->name, 'Clayman');
        $this->assertEquals($users[1]->Album[1]->name, 'Colony');
        $this->assertEquals($users[1]->Album[1]->Song[0]->title, 'Colony');
        $this->assertEquals($users[1]->Album[1]->Song[1]->title, 'Ordinary Story');

        $this->assertEquals($users[1]->Book[0]->name, 'Candide');
        $this->assertEquals($users[1]->Book[0]->Author[0]->name, 'Someone');
        $this->assertEquals($users[1]->Book[0]->Author[1]->name, 'Voltaire');
        $this->assertEquals($users[1]->Book[1]->name, 'Zadig');
        $this->assertEquals($users[1]->Book[1]->Author[0]->name, 'Someone');
        $this->assertEquals($users[1]->Book[1]->Author[1]->name, 'Voltaire');
    }

    public function testMultipleOneToManyFetchingWithOrderBy()
    {
        $query = new \Doctrine1\Query();

        $users = $query->query('FROM User.Album.Song WHERE User.id IN (4,5) ORDER BY User.Album.Song.title DESC');
    }
}
