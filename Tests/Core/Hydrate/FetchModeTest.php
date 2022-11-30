<?php
namespace Tests\Core\Hydrate;

use Tests\DoctrineUnitTestCase;

class FetchModeTest extends DoctrineUnitTestCase
{
    public function testFetchArraySupportsOneToManyRelations(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p')->where("u.name = 'zYne'");

        $users = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $this->assertTrue(is_array($users));

        $this->assertEquals(count($users), 1);
    }

    public function testFetchArraySupportsOneToManyRelations2(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p');

        $users = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $this->assertTrue(is_array($users));

        $this->assertEquals(count($users), 8);
    }

    public function testFetchArraySupportsOneToManyRelations3(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p')->where("u.name = 'Jean Reno'");

        $users = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $this->assertTrue(is_array($users));

        $this->assertEquals(count($users), 1);
        $this->assertEquals(count($users[0]['Phonenumber']), 3);
    }

    public function testFetchArraySupportsOneToOneRelations(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.*, e.*')->from('User u')->innerJoin('u.Email e');

        $users = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $this->assertEquals(count($users), 8);
        $this->assertEquals($users[0]['Email']['address'], 'zYne@example.com');
    }

    public function testFetchArraySupportsOneToOneRelations2(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.*, e.*')->from('User u')->innerJoin('u.Email e')->where("u.name = 'zYne'");

        $users = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $this->assertEquals(count($users), 1);
        $this->assertEquals($users[0]['Email']['address'], 'zYne@example.com');
    }

    public function testFetchRecordSupportsOneToOneRelations(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.*, e.*')->from('User u')->innerJoin('u.Email e');

        $users = $q->execute([], \Doctrine1\Core::HYDRATE_RECORD);
        $count = count(static::$conn);

        $this->assertEquals(count($users), 8);

        $this->assertEquals($users[0]['Email']['address'], 'zYne@example.com');
        $this->assertTrue($users[0] instanceof \User);
        $this->assertTrue($users instanceof \Doctrine1\Collection);
        $this->assertEquals(\Doctrine1\Record\State::CLEAN, $users[0]->state());
        $this->assertEquals($users[0]->id, 4);

        $this->assertTrue($users[0]['Email'] instanceof \Email);
        $this->assertEquals($users[0]['email_id'], 1);

        // Make sure our query count doesn't exceed what it took
        // to execute the first query (not doing n+1 queries on relations
        // if selected in the initial query)
        $this->assertEquals(count(static::$conn), $count);
    }

    public function testFetchRecordSupportsOneToManyRelations(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p');
        $users = $q->execute([], \Doctrine1\Core::HYDRATE_RECORD);
        $count = count(static::$conn);

        $this->assertEquals(count($users), 8);
        $this->assertTrue($users[0] instanceof \User);
        $this->assertEquals(\Doctrine1\Record\State::CLEAN, $users[0]->state());
        $this->assertTrue($users instanceof \Doctrine1\Collection);
        $this->assertTrue($users[0]->Phonenumber instanceof \Doctrine1\Collection);

        $this->assertEquals(count(static::$conn), $count);
    }

    public function testFetchRecordSupportsSimpleFetching(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.*')->from('User u');
        $users = $q->execute([], \Doctrine1\Core::HYDRATE_RECORD);
        $count = static::$conn->count();

        $this->assertEquals(count($users), 8);
        $this->assertTrue($users[0] instanceof \User);
        $this->assertEquals(\Doctrine1\Record\State::CLEAN, $users[0]->state());

        $this->assertEquals(static::$conn->count(), $count);
    }

    public function testFetchArrayNull(): void
    {
        $u          = new \User();
        $u->name    = 'fetch_array_test';
        $u->created = null;
        $u->save();

        $q = new \Doctrine1\Query();
        $q->select('u.*')->from('User u')->where('u.id = ?');
        $users = $q->execute([$u->id], \Doctrine1\Core::HYDRATE_ARRAY);
        $this->assertEquals($users[0]['created'], null);
    }

    public function testHydrateNone(): void
    {
        $u          = new \User();
        $u->name    = 'fetch_array_test';
        $u->created = null;
        $u->save();

        $q = new \Doctrine1\Query();
        $q->select('COUNT(u.id) num')->from('User u')->where('u.id = ?');
        $res = $q->execute([$u->id], \Doctrine1\Core::HYDRATE_NONE);
        $this->assertEquals(1, $res[0][0]);
    }
}
