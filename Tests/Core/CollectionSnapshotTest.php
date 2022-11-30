<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class CollectionSnapshotTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['Entity', 'User', 'Group', 'GroupUser', 'Account', 'Album', 'Phonenumber', 'Email', 'Book'];

    public function testDiffForSimpleCollection(): void
    {
        $q = \Doctrine1\Query::create()->from('User u')->orderby('u.id');

        $coll = $q->execute();
        $this->assertCount(8, $coll);

        unset($coll[0]);
        unset($coll[1]);

        $coll[]->name = 'new \user';

        $this->assertCount(7, $coll);
        $this->assertCount(8, $coll->getSnapshot());

        $count = static::$conn->count();

        $coll->save();

        static::$connection->clear();
        $coll = \Doctrine1\Query::create()->from('User u')->execute();
        $this->assertCount(7, $coll);
    }

    public function testDiffForOneToManyRelatedCollection(): void
    {
        $q = new \Doctrine1\Query();
        $q->from('User u LEFT JOIN u.Phonenumber p')
            ->where('u.id = 8');

        $coll = $q->execute();

        $this->assertCount(1, $coll);

        $this->assertCount(3, $coll[0]->Phonenumber);
        $this->assertTrue($coll[0]->Phonenumber instanceof \Doctrine1\Collection);

        unset($coll[0]->Phonenumber[0]);
        $coll[0]->Phonenumber->remove(2);

        $this->assertCount(3, $coll[0]->Phonenumber->getSnapshot());
        $coll[0]->save();

        $this->assertCount(1, $coll[0]->Phonenumber);

        static::$connection->clear();

        $q = new \Doctrine1\Query();
        $q = \Doctrine1\Query::create()->from('User u LEFT JOIN u.Phonenumber p')->where('u.id = 8');

        $coll = $q->execute();

        $this->assertCount(1, $coll[0]->Phonenumber);
    }

    public function testDiffForManyToManyRelatedCollection(): void
    {
        $user                 = new \User();
        $user->name           = 'zYne';
        $user->Group[0]->name = 'PHP';
        $user->Group[1]->name = 'Web';
        $user->save();

        static::$connection->clear();

        $users = \Doctrine1\Query::create()->from('User u LEFT JOIN u.Group g')
            ->where('u.id = ' . $user->id)->execute();

        $this->assertEquals('PHP', $users[0]->Group[0]->name);
        $this->assertEquals('Web', $users[0]->Group[1]->name);
        $this->assertCount(2, $user->Group->getSnapshot());
        unset($user->Group[0]);

        $user->save();
        $this->assertEquals(count($user->Group), 1);

        $this->assertCount(1, $user->Group->getSnapshot());
        unset($user->Group[1]);
        $this->assertCount(1, $user->Group->getSnapshot());

        $count = count(static::$conn);
        $user->save();

        $this->assertCount(0, $user->Group->getSnapshot());

        static::$conn->clear();

        $users = \Doctrine1\Query::create()->from('User u LEFT JOIN u.Group g')
            ->where('u.id = ' . $user->id)->execute();

        $this->assertCount(0, $user->Group);
    }
}
