<?php
namespace Tests\Core\Hydrate;

use Tests\DoctrineUnitTestCase;

class CollectionInitializationTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
        $user       = new \User();
        $user->name = 'romanb';

        $user->Phonenumber[0]->phonenumber = '112';
        $user->Phonenumber[1]->phonenumber = '110';

        $user->save();
    }

    protected static array $tables = ['Entity', 'Phonenumber'];

    public function testCollectionsAreReinitializedOnHydration()
    {
        // query for user with first phonenumber.
        $q = \Doctrine1\Query::create();
        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p')
            ->where("p.phonenumber = '112'");

        $users = $q->execute();
        $this->assertEquals(1, count($users));
        $this->assertEquals(1, count($users[0]->Phonenumber));
        $this->assertEquals('112', $users[0]->Phonenumber[0]->phonenumber);

        // now query again. this time for the other phonenumber. collection should be re-initialized.
        $q = \Doctrine1\Query::create();
        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p')
            ->where("p.phonenumber = '110'");
        $users = $q->execute();
        $this->assertEquals(1, count($users));
        $this->assertEquals(1, count($users[0]->Phonenumber));
        $this->assertEquals('110', $users[0]->Phonenumber[0]->phonenumber);

        // now query again. this time for both phonenumbers. collection should be re-initialized.
        $q = \Doctrine1\Query::create();
        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p');
        $users = $q->execute();
        $this->assertEquals(1, count($users));
        $this->assertEquals(2, count($users[0]->Phonenumber));
        $this->assertEquals('112', $users[0]->Phonenumber[0]->phonenumber);
        $this->assertEquals('110', $users[0]->Phonenumber[1]->phonenumber);

        // now query AGAIN for both phonenumbers. collection should be re-initialized (2 elements, not 4).
        $q = \Doctrine1\Query::create();
        $q->select('u.*, p.*')->from('User u')->innerJoin('u.Phonenumber p');
        $users = $q->execute();
        $this->assertEquals(1, count($users));
        $this->assertEquals(2, count($users[0]->Phonenumber));
        $this->assertEquals('112', $users[0]->Phonenumber[0]->phonenumber);
        $this->assertEquals('110', $users[0]->Phonenumber[1]->phonenumber);
    }
}
