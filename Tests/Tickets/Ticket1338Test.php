<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1338Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $q = \Doctrine1\Query::create()
            ->from('User u');
        $users = $q->execute();

        $array = $users->toKeyValueArray('id', 'name');
        $this->assertEquals(
            $array,
            [
            4  => 'zYne',
            5  => 'Arnold Schwarzenegger',
            6  => 'Michael Caine',
            7  => 'Takeshi Kitano',
            8  => 'Sylvester Stallone',
            9  => 'Kurt Russell',
            10 => 'Jean Reno',
            11 => 'Edward Furlong',
            ]
        );
    }
}
