<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket786Test extends DoctrineUnitTestCase
{
    public function testConflictingQueriesHydration()
    {
        // Query for the User with Phonenumbers joined in
        // So you can access User->Phonenumber instanceof \Doctrine1\Collection
        $query = new \Doctrine1\Query();
        $query->from('User u, u.Phonenumber p');

        $user1 = $query->execute()->getFirst();

        // Now query for the same data, without the Phonenumber
        $query = new \Doctrine1\Query();
        $query->from('User u');

        $user2 = $query->execute()->getFirst();

        // Now if we try and see if Phonenumber is present in $user1 after the 2nd query
        $this->assertTrue(isset($user1->Phonenumber), 'Phonenumber overwritten by 2nd query hydrating');
    }
}
