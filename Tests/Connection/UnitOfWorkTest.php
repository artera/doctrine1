<?php
namespace Tests\Connection;

use Tests\DoctrineUnitTestCase;

class UnitOfWorkTest extends DoctrineUnitTestCase
{
    public function testFlush(): void
    {
        $user = static::$connection->getTable('User')->find(4);
        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $user   = static::$connection->create('User');
        $record = static::$connection->create('Phonenumber');

        $user->Email = new \Email;
        $user->Email->address = 'example@drinkmore.info';
        $this->assertInstanceOf(\Email::class, $user->Email);

        $user->name           = 'Example user';
        $user->Group[0]->name = 'Example group 1';
        $user->Group[1]->name = 'Example group 2';

        $user->Phonenumber[0]->phonenumber = '123 123';

        $user->Phonenumber[1]->phonenumber = '321 2132';
        $user->Phonenumber[2]->phonenumber = '123 123';
        $user->Phonenumber[3]->phonenumber = '321 2132';



        $this->assertTrue($user->Phonenumber[0]->entity_id instanceof \User);
        $this->assertTrue($user->Phonenumber[2]->entity_id instanceof \User);

        static::$connection->flush();

        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $this->assertEquals(count($user->Group), 2);
        $user2 = $user;

        $user = static::$connection->getTable('User')->find($user->id);

        $this->assertEquals($user->id, $user2->id);

        $this->assertTrue(is_numeric($user->id));

        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));
        $this->assertEquals($user->Phonenumber->count(), 4);
        $this->assertEquals($user->Group->count(), 2);


        $user = static::$connection->getTable('User')->find(5);

        $pf = static::$connection->getTable('Phonenumber');

        $this->assertTrue($user->Phonenumber instanceof \Doctrine_Collection);
        $this->assertTrue($user->Phonenumber->count() == 3);

        $coll = new \Doctrine_Collection($pf);

        $user->Phonenumber = $coll;
        $this->assertTrue($user->Phonenumber->count() == 0);

        static::$connection->flush();
        unset($user);
        $user = static::$connection->getTable('User')->find(5);

        $this->assertEquals($user->Phonenumber->count(), 0);

        // ADDING REFERENCES

        $user->Phonenumber[0]->phonenumber = '123 123';
        $this->assertTrue(is_numeric($user->Phonenumber[0]->entity_id));

        $user->Phonenumber[1]->phonenumber = '123 123';
        static::$connection->flush();


        $this->assertEquals($user->Phonenumber->count(), 2);

        unset($user);
        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Phonenumber->count(), 2);

        $user->Phonenumber[3]->phonenumber = '123 123';
        static::$connection->flush();

        $this->assertEquals($user->Phonenumber->count(), 3);
        unset($user);
        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Phonenumber->count(), 3);

        // DELETING REFERENCES

        $user->Phonenumber->delete();

        $this->assertEquals($user->Phonenumber->count(), 0);
        unset($user);
        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Phonenumber->count(), 0);

        // ADDING REFERENCES WITH STRING KEYS

        $user->Phonenumber['home']->phonenumber = '123 123';
        $user->Phonenumber['work']->phonenumber = '444 444';

        $this->assertEquals($user->Phonenumber->count(), 2);
        static::$connection->flush();

        $this->assertEquals($user->Phonenumber->count(), 2);
        unset($user);
        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Phonenumber->count(), 2);

        // REPLACING ONE-TO-MANY REFERENCE

        unset($coll);
        $coll                      = new \Doctrine_Collection($pf);
        $coll[0]->phonenumber      = '123 123';
        $coll['home']->phonenumber = '444 444';
        $coll['work']->phonenumber = '444 444';




        $user->Phonenumber = $coll;
        static::$connection->flush();
        $this->assertEquals($user->Phonenumber->count(), 3);
        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Phonenumber->count(), 3);


        // ONE-TO-ONE REFERENCES

        $user->Email->address = 'drinker@drinkmore.info';
        $this->assertTrue($user->Email instanceof \Email);
        static::$connection->flush();
        $this->assertTrue($user->Email instanceof \Email);
        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Email->address, 'drinker@drinkmore.info');
        $id = $user->Email->id;

        // REPLACING ONE-TO-ONE REFERENCES

        $email          = static::$connection->create('Email');
        $email->address = 'absolutist@nottodrink.com';
        $user->Email    = $email;

        $this->assertTrue($user->Email instanceof \Email);
        $this->assertEquals($user->Email->address, 'absolutist@nottodrink.com');
        static::$connection->flush();
        unset($user);

        $user = static::$connection->getTable('User')->find(5);
        $this->assertTrue($user->Email instanceof \Email);
        $this->assertEquals($user->Email->address, 'absolutist@nottodrink.com');

        $emails = static::$connection->query("FROM Email WHERE Email.id = $id");
        //$this->assertEquals(count($emails),0);
    }

    public function testTransactions(): void
    {
        static::$connection->beginTransaction();
        $this->assertEquals(\Doctrine_Transaction_State::ACTIVE(), static::$connection->transaction->getState());
        static::$connection->commit();
        $this->assertEquals(\Doctrine_Transaction_State::SLEEP(), static::$connection->transaction->getState());

        static::$connection->beginTransaction();

        $user = static::$connection->getTable('User')->find(6);

        $user->name = 'Jack Daniels';
        static::$connection->flush();
        static::$connection->commit();

        $user = static::$connection->getTable('User')->find(6);
        $this->assertEquals($user->name, 'Jack Daniels');
    }
}
