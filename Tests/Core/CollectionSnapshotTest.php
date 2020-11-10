<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class CollectionSnapshotTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['Entity', 'User', 'Group', 'GroupUser', 'Account', 'Album', 'Phonenumber', 'Email', 'Book'];

    public function testDiffForSimpleCollection()
    {
        $q = \Doctrine_Query::create()->from('User u')->orderby('u.id');

        $coll = $q->execute();
        $this->assertEquals($coll->count(), 8);

        unset($coll[0]);
        unset($coll[1]);

        $coll[]->name = 'new \user';

        $this->assertEquals($coll->count(), 7);
        $this->assertEquals(count($coll->getSnapshot()), 8);

        $count = static::$conn->count();

        $coll->save();

        static::$connection->clear();
        $coll = \Doctrine_Query::create()->from('User u')->execute();
        $this->assertEquals($coll->count(), 7);
    }

    public function testDiffForOneToManyRelatedCollection()
    {
        $q = new \Doctrine_Query();
        $q->from('User u LEFT JOIN u.Phonenumber p')
            ->where('u.id = 8');

        $coll = $q->execute();

        $this->assertEquals($coll->count(), 1);

        $this->assertEquals($coll[0]->Phonenumber->count(), 3);
        $this->assertTrue($coll[0]->Phonenumber instanceof \Doctrine_Collection);

        unset($coll[0]->Phonenumber[0]);
        $coll[0]->Phonenumber->remove(2);

        $this->assertEquals(count($coll[0]->Phonenumber->getSnapshot()), 3);
        $coll[0]->save();

        $this->assertEquals($coll[0]->Phonenumber->count(), 1);

        static::$connection->clear();

        $q = new \Doctrine_Query();
        $q = \Doctrine_Query::create()->from('User u LEFT JOIN u.Phonenumber p')->where('u.id = 8');

        $coll = $q->execute();

        $this->assertEquals($coll[0]->Phonenumber->count(), 1);
    }

    public function testDiffForManyToManyRelatedCollection()
    {
        $user                 = new \User();
        $user->name           = 'zYne';
        $user->Group[0]->name = 'PHP';
        $user->Group[1]->name = 'Web';
        $user->save();

        static::$connection->clear();

        $users = \Doctrine_Query::create()->from('User u LEFT JOIN u.Group g')
            ->where('u.id = ' . $user->id)->execute();

        $this->assertEquals($users[0]->Group[0]->name, 'PHP');
        $this->assertEquals($users[0]->Group[1]->name, 'Web');
        $this->assertEquals(count($user->Group->getSnapshot()), 2);
        unset($user->Group[0]);

        $user->save();
        $this->assertEquals(count($user->Group), 1);

        $this->assertEquals(count($user->Group->getSnapshot()), 1);
        unset($user->Group[1]);
        $this->assertEquals(count($user->Group->getSnapshot()), 1);

        $count = count(static::$conn);
        $user->save();

        $this->assertEquals(count($user->Group->getSnapshot()), 0);

        static::$conn->clear();

        $users = \Doctrine_Query::create()->from('User u LEFT JOIN u.Group g')
            ->where('u.id = ' . $user->id)->execute();

        $this->assertEquals(count($user->Group), 0);
    }
}
