<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class LimitTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        static::$tables[] = 'Photo';
        static::$tables[] = 'Tag';
        static::$tables[] = 'Phototag';

        parent::prepareTables();
    }

    public function testLimitWithNormalManyToMany(): void
    {
        $coll                 = new \Doctrine1\Collection(static::$connection->getTable('Photo'));
        $tag                  = new \Tag();
        $tag->tag             = 'Some tag';
        $coll[0]->Tag[0]      = $tag;
        $coll[0]->name        = 'photo 1';
        $coll[1]->Tag[0]      = $tag;
        $coll[1]->name        = 'photo 2';
        $coll[2]->Tag[0]      = $tag;
        $coll[2]->name        = 'photo 3';
        $coll[3]->Tag[0]->tag = 'Other tag';
        $coll[3]->name        = 'photo 4';
        static::$connection->flush();

        $q = new \Doctrine1\Query();
        $q->from('Photo')->where('Photo.Tag.id = ?')->orderby('Photo.id DESC')->limit(100);
        $this->assertMatchesSnapshot($q->getSqlQuery());

        $photos = $q->fetchArray([1]);
        $this->assertCount(3, $photos);
    }

    public function testLimitWithOneToOneLeftJoin(): void
    {
        $q = new \Doctrine1\Query();
        $q->select('u.id, e.*')->from('User u, u.Email e')->limit(5);
        $this->assertMatchesSnapshot($q->getSqlQuery());

        $users = $q->execute();
        $this->assertCount(5, $users);
    }

    public function testLimitWithOneToOneInnerJoin(): void
    {
        $q = new \Doctrine1\Query();
        $q->select('u.id, e.*')->from('User u, u:Email e')->limit(5);
        $this->assertMatchesSnapshot($q->getSqlQuery());

        $users = $q->execute();
        $this->assertCount(5, $users);
    }

    public function testLimitWithOneToManyLeftJoin(): void
    {
        $q = new \Doctrine1\Query();
        $q->select('u.id, p.*')->from('User u, u.Phonenumber p')->limit(5);
        $this->assertMatchesSnapshot($q->getSqlQuery());

        $users = $q->execute();
        $count = static::$conn->count();
        $this->assertCount(5, $users);
        $users[0]->Phonenumber[0];
        $this->assertEquals($count, static::$conn->count());

        $q->offset(2);

        $users = $q->execute();
        $count = static::$conn->count();
        $this->assertCount(5, $users);
        $users[3]->Phonenumber[0];
        $this->assertEquals($count, static::$conn->count());
    }

    public function testLimitWithOneToManyLeftJoinAndCondition(): void
    {
        $q = new \Doctrine1\Query();
        $q->select('User.name')->from('User')->where("User.Phonenumber.phonenumber LIKE '%123%'")->limit(5);

        $users = $q->execute();

        $this->assertCount(5, $users);

        $this->assertEquals($users[0]->name, 'zYne');
        $this->assertEquals($users[1]->name, 'Arnold Schwarzenegger');
        $this->assertEquals($users[2]->name, 'Michael Caine');
        $this->assertEquals($users[3]->name, 'Sylvester Stallone');
        $this->assertEquals($users[4]->name, 'Jean Reno');
        $this->assertMatchesSnapshot($q->getSqlQuery());
    }


    public function testLimitWithOneToManyLeftJoinAndOrderBy()
    {
        $q = new \Doctrine1\Query();
        $q->select('User.name')->distinct()->from('User')->where("User.Phonenumber.phonenumber LIKE '%123%'")->orderby('User.Email.address')->limit(5);


        $users = $q->execute();

        $this->assertEquals($users[0]->name, 'Arnold Schwarzenegger');
        $this->assertEquals($users[1]->name, 'Michael Caine');
        $this->assertEquals($users[2]->name, 'Jean Reno');
        $this->assertEquals($users[3]->name, 'Sylvester Stallone');
        $this->assertEquals($users[4]->name, 'zYne');

        $this->assertEquals($users->count(), 5);
    }

    public function testLimitWithOneToManyInnerJoin()
    {
        $q = new \Doctrine1\Query();
        $q->select('u.id, p.*')->from('User u INNER JOIN u.Phonenumber p');
        $q->limit(5);

        $users = $q->execute();
        $count = static::$conn->count();
        $this->assertEquals($users->count(), 5);
        $users[0]->Phonenumber[0];
        $this->assertEquals($count, static::$conn->count());


        $q->offset(2);

        $users = $q->execute();
        $count = static::$conn->count();
        $this->assertEquals($users->count(), 5);
        $users[3]->Phonenumber[0];
        $this->assertEquals($count, static::$conn->count());
        $this->assertMatchesSnapshot($q->getSqlQuery());
    }

    public function testLimitWithPreparedQueries()
    {
        $q = new \Doctrine1\Query();
        $q->select('u.id, p.id')->from('User u LEFT JOIN u.Phonenumber p');
        $q->where('u.name = ?', ['zYne']);
        $q->limit(5);
        $users = $q->execute();

        $this->assertEquals($users->count(), 1);
        $count = static::$conn->count();
        $users[0]->Phonenumber[0];
        $this->assertEquals($count, static::$conn->count());
        $this->assertMatchesSnapshot($q->getSqlQuery());

        $q = new \Doctrine1\Query();
        $q->select('u.id, p.id')->from('User u LEFT JOIN u.Phonenumber p');
        $q->where('u.name LIKE ? OR u.name LIKE ?');
        $q->limit(5);

        $users = $q->execute(['%zYne%', '%Arnold%']);
        $this->assertEquals($users->count(), 2);


        $count = static::$conn->count();
        $users[0]->Phonenumber[0];
        $this->assertEquals($count, static::$conn->count());
        $this->assertMatchesSnapshot($q->getSqlQuery());
    }

    public function testConnectionFlushing()
    {
        $q = new \Doctrine1\Query();
        $q->from('User.Phonenumber');
        $q->where('User.name = ?', ['zYne']);
        $q->limit(5);

        $users = $q->execute();
        $this->assertMatchesSnapshot($q->getSqlQuery());

        $this->assertEquals($users->count(), 1);
        //static::$connection->flush();
    }
    public function testLimitWithManyToManyColumnAggInheritanceLeftJoin()
    {
        $q = new \Doctrine1\Query();
        $q->from('User.Group')->limit(5);

        $users = $q->execute();

        $this->assertEquals($users->count(), 5);

        $user = static::$connection->getTable('User')->find(5);

        $user->Group[1]->name = 'Tough guys inc.';

        $user->Group[2]->name = 'Terminators';

        $user2 = static::$connection->getTable('User')->find(4);
        //$user2->Group = $user->Group;
        $user2->Group   = new \Doctrine1\Collection('Group');
        $user2->Group[] = $user->Group[0];
        $user2->Group[] = $user->Group[1];
        $user2->Group[] = $user->Group[2];

        $user3 = static::$connection->getTable('User')->find(6);
        //$user3->Group = $user->Group;
        $user3->Group   = new \Doctrine1\Collection('Group');
        $user3->Group[] = $user->Group[0];
        $user3->Group[] = $user->Group[1];
        $user3->Group[] = $user->Group[2];

        $this->assertEquals($user->Group[0]->name, 'Action Actors');
        $this->assertEquals(count($user->Group), 3);
        $this->assertEquals(count($user2->Group), 3);
        $this->assertEquals(count($user3->Group), 3);

        static::$connection->flush();

        $this->assertEquals($user->Group[0]->name, 'Action Actors');
        $this->assertEquals(count($user->Group), 3);

        $q = new \Doctrine1\Query();
        $q->from('User')->where('User.Group.id = ?')->orderby('User.id ASC')->limit(5);

        $users = $q->execute([$user->Group[1]->id]);

        $this->assertEquals($users->count(), 3);

        static::$connection->clear();
        $q = new \Doctrine1\Query();
        $q->from('User')->where('User.Group.id = ?')->orderby('User.id DESC');
        $users = $q->execute([$user->Group[1]->id]);

        $this->assertEquals($users->count(), 3);
    }

    public function testLimitAttribute()
    {
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_QUERY_LIMIT, \Doctrine1\Core::LIMIT_ROWS);

        static::$connection->clear();
        $q = new \Doctrine1\Query();
        $q->from('User')->where('User.Group.name = ?')->orderby('User.id DESC')->limit(5);
        $users = $q->execute(['Tough guys inc.']);

        $this->assertCount(3, $users);
        $this->assertMatchesSnapshot($q->getSqlQuery());
        static::$manager->setAttribute(\Doctrine1\Core::ATTR_QUERY_LIMIT, \Doctrine1\Core::LIMIT_RECORDS);
    }

    public function testLimitWithManyToManyAndColumnAggregationInheritance()
    {
        $q = new \Doctrine1\Query();
        $q->from('User u, u.Group g')->where('g.id > 1')->orderby('u.name DESC')->limit(10);
    }

    public function testLimitNoticesOrderbyJoins()
    {
        $q = new \Doctrine1\Query();

        $q->from('Photo p')
            ->leftJoin('p.Tag t')
            ->orderby('t.id DESC')->limit(10);
        $this->assertMatchesSnapshot($q->getSqlQuery());
    }

    public function testLimitSubqueryNotNeededIfSelectSingleFieldDistinct()
    {
        $q = new \Doctrine1\Query();
        $q->select('u.id')->distinct()->from('User u LEFT JOIN u.Phonenumber p');
        $q->where('u.name = ?');
        $q->limit(5);

        $users = $q->execute(['zYne']);
        $this->assertCount(1, $users);
        $this->assertMatchesSnapshot($q->getSqlQuery());
    }
}
