<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class BatchIteratorTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['EntityAddress', 'Entity', 'User', 'Group', 'Address', 'Email', 'Phonenumber'];

    public function testIterator()
    {
        $graph    = new \Doctrine_Query(static::$connection);
        $entities = $graph->query('FROM Entity');
        $i        = 0;
        foreach ($entities as $entity) {
            $this->assertEquals(gettype($entity->name), 'string');
            $i++;
        }
        $this->assertTrue($i == $entities->count());

        $user = $graph->query('FROM User');
        foreach ($user[1]->Group as $group) {
            $this->assertTrue(is_string($group->name));
        }

        $user       = new \User();
        $user->name = 'tester';

        $user->Address[0]->address = 'street 1';
        $user->Address[1]->address = 'street 2';

        $this->assertEquals($user->name, 'tester');
        $this->assertEquals($user->Address[0]->address, 'street 1');
        $this->assertEquals($user->Address[1]->address, 'street 2');

        foreach ($user->Address as $address) {
            $a[] = $address->address;
        }
        $this->assertEquals($a, ['street 1', 'street 2']);

        $user->save();

        $user = $user->getTable()->find($user->id);
        $this->assertEquals($user->name, 'tester');
        $this->assertEquals($user->Address[0]->address, 'street 1');
        $this->assertEquals($user->Address[1]->address, 'street 2');

        $user = $user->getTable()->find($user->id);
        $a    = [];
        foreach ($user->Address as $address) {
            $a[] = $address->address;
        }
        $this->assertEquals($a, ['street 1', 'street 2']);

        $user = $graph->query('FROM User');
    }
}
