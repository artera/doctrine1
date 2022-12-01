<?php

namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket576Test extends DoctrineUnitTestCase
{
    protected static array $tables = ['Entity'];

    public static function prepareData(): void
    {
    }

    public function testInit()
    {
        $entity            = new \Entity();
        $entity->name      = 'myname';
        $entity->loginname = 'test';
        $entity->save();
    }

    public function testBug()
    {
        // load our user and our collection of pages
        $user = \Doctrine1\Query::create()->from('Entity')->fetchOne();
        $this->assertEquals($user->name, 'myname');
        $this->assertEquals($user->loginname, 'test');

        $user->name = null;
        $this->assertEquals($user->name, null);

        $data = \Doctrine1\Query::create()
            ->select('name')
            ->from('Entity')
            ->fetchOne([], \Doctrine1\HydrationMode::Array);

        $user->hydrate($data);
        $this->assertEquals($user->name, 'myname');
        $this->assertEquals($user->loginname, 'test'); // <<----- this is what the bug is about
    }
}
