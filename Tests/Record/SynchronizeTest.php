<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class SynchronizeTest extends DoctrineUnitTestCase
{
    protected static int $previousGroup;

    public static function prepareTables(): void
    {
        parent::prepareTables();
    }

    public static function prepareData(): void
    {
        $user                              = new \User();
        $user->name                        = 'John';
        $user->Email = new \Email();
        $user->Email->address              = 'john@mail.com';
        $user->Phonenumber[0]->phonenumber = '555 123';
        $user->Phonenumber[1]->phonenumber = '555 448';
        $user->save();

        // Create an existing group
        $group       = new \Group();
        $group->name = 'Group One';
        $group->save();
        static::$previousGroup = $group['id'];
    }

    public function testSynchronizeRecord()
    {
        $user      = \Doctrine1\Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $userArray = $user->toArray(true);
        $this->assertEquals($user->Phonenumber->count(), 2);
        $this->assertEquals($user->Phonenumber[0]->phonenumber, '555 123');

        // modify a Phonenumber
        $userArray['Phonenumber'][0]['phonenumber'] = '555 321';

        // delete a Phonenumber
        array_pop($userArray['Phonenumber']);

        // add group
        $userArray['Group'][]['name'] = 'New \Group'; // This is a n-m relationship
        // add a group which exists
        $userArray['Group'][1]['_identifier'] = static::$previousGroup; // This is a n-m relationship where the group was made in prepareData

        $user->synchronizeWithArray($userArray);
        $this->assertEquals($user->Phonenumber->count(), 1);
        $this->assertEquals($user->Phonenumber[0]->phonenumber, '555 321');
        $this->assertEquals($user->Group[0]->name, 'New \Group');
        $this->assertEquals($user->Group[1]->name, 'Group One');

        // change Email
        $userArray['Email']['address'] = 'johndow@mail.com';
        $user->synchronizeWithArray($userArray);

        $this->assertEquals($user->Email->address, 'johndow@mail.com');

        $user->save();
    }

    public function testSynchronizeAfterSaveRecord()
    {
        $user = \Doctrine1\Query::create()->from('User u, u.Group g, u.Email e, u.Phonenumber p')->fetchOne();
        $this->assertEquals($user->Phonenumber->count(), 1);
        $this->assertEquals($user->Phonenumber[0]->phonenumber, '555 321');
        $this->assertEquals($user->Email->address, 'johndow@mail.com');
        $this->assertEquals($user->Group->count(), 2);
        // Order gets changed on fetch, need to check if both groups exist
        foreach ($user->Group as $group) {
            $groups[] = $group->name;
        }
        $this->assertTrue(in_array('Group One', $groups));
        $this->assertTrue(in_array('New \Group', $groups));
    }

    public function testSynchronizeAddRecord()
    {
        $user                       = \Doctrine1\Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $userArray                  = $user->toArray(true);
        $userArray['Phonenumber'][] = ['phonenumber' => '333 238'];

        $user->synchronizeWithArray($userArray);

        $this->assertEquals($user->Phonenumber->count(), 2);
        $this->assertEquals($user->Phonenumber[1]->phonenumber, '333 238');
        $user->save();
    }

    public function testSynchronizeAfterAddRecord()
    {
        $user   = \Doctrine1\Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $phones = [];

        $this->assertEquals($user->Phonenumber->count(), 2);
        foreach ($user->Phonenumber as $phone) {
            $phones[] = $phone->phonenumber;
        }
        $this->assertTrue(in_array('333 238', $phones));
        $this->assertTrue(in_array('555 321', $phones));
    }

    public function testSynchronizeRemoveRecord()
    {
        $user      = \Doctrine1\Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $userArray = $user->toArray(true);
        unset($userArray['Phonenumber']);
        unset($userArray['Email']);
        unset($userArray['email_id']);

        $user->synchronizeWithArray($userArray);
        $this->assertEquals($user->Phonenumber->count(), 0);
        $this->assertTrue(!isset($user->Email));
        $user->save();
    }

    public function testSynchronizeAfterRemoveRecord()
    {
        $user = \Doctrine1\Query::create()->from('User u, u.Email, u.Phonenumber')->fetchOne();
        $this->assertEquals($user->Phonenumber->count(), 0);
        $this->assertTrue(!isset($user->Email));
    }
}
