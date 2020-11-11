<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class FromArrayTest extends DoctrineUnitTestCase
{
    static int $previousGroup;

    public static function prepareTables(): void
    {
        parent::prepareTables();
    }

    public static function prepareData(): void
    {
        // Create an existing group
        $group       = new \Group();
        $group->name = 'Group One';
        $group->save();
        static::$previousGroup = $group['id'];
    }

    public function testFromArrayRecord()
    {
        $user      = new \User();
        $userArray = $user->toArray();

        // add a Phonenumber
        $userArray['Phonenumber'][0]['phonenumber'] = '555 321';

        // add an Email address
        $userArray['Email']['address'] = 'johndow@mail.com';

        // add group
        $userArray['Group'][0]['name'] = 'New \Group'; // This is a n-m relationship
        // add a group which exists
        $userArray['Group'][1]['_identifier'] = static::$previousGroup; // This is a n-m relationship where the group was made in prepareData

        $user->fromArray($userArray);

        $this->assertEquals($user->Phonenumber->count(), 1);
        $this->assertEquals($user->Phonenumber[0]->phonenumber, '555 321');
        $this->assertEquals($user->Group[0]->name, 'New \Group');
        $this->assertEquals($user->Group[1]->name, 'Group One');

        $user->save();
    }

    public function testFromArrayAfterSaveRecord()
    {
        // This is fetching the user made in the previous test apparently
        $user   = \Doctrine_Query::create()->from('User u, u.Email, u.Phonenumber, u.Group')->fetchOne();
        $groups = [];

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
}
