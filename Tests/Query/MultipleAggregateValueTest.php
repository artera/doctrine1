<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class MultipleAggregateValueTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['Book', 'User', 'Album'];

    public static function prepareData(): void
    {
    }

    public function testInitData()
    {
        $user       = new \User();
        $user->name = 'jon';

        $user->Album[0] = new \Album();
        $user->Album[1] = new \Album();
        $user->Album[2] = new \Album();

        $user->Book[0] = new \Book();
        $user->Book[1] = new \Book();
        $user->save();
    }

    public function testMultipleAggregateValues()
    {
        $query = new \Doctrine_Query();
        $query->select('u.*, COUNT(DISTINCT b.id) num_books, COUNT(DISTINCT a.id) num_albums');
        $query->from('User u');
        $query->leftJoin('u.Album a, u.Book b');
        $query->where("u.name = 'jon'");
        $query->limit(1);

        $user = $query->execute()->getFirst();

        $name       = $user->name;
        $num_albums = $user->num_albums;
        $num_books  = $user->num_books;

        $this->assertEquals($num_albums, 3);
        $this->assertEquals($num_books, 2);
    }
    public function testMultipleAggregateValuesWithArrayFetching()
    {
        $query = new \Doctrine_Query();
        $query->select('u.*, COUNT(DISTINCT b.id) num_books, COUNT(DISTINCT a.id) num_albums');
        $query->from('User u');
        $query->leftJoin('u.Album a, u.Book b');
        $query->where("u.name = 'jon'");
        $query->limit(1);

        $users = $query->execute([], \Doctrine_Core::HYDRATE_ARRAY);

        $name       = $users[0]['name'];
        $num_albums = $users[0]['num_albums'];
        $num_books  = $users[0]['num_books'];

        $this->assertEquals($num_albums, 3);
        $this->assertEquals($num_books, 2);
    }
}
