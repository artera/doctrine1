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
        $user = \Doctrine_Query::create()->from('Entity')->fetchOne();
        $this->assertEquals($user->name, 'myname');
        $this->assertEquals($user->loginname, 'test');

        $user->name = null;
        $this->assertEquals($user->name, null);

        $data = \Doctrine_Query::create()
            ->select('name')
            ->from('Entity')
            ->fetchOne([], \Doctrine_Core::HYDRATE_ARRAY);

        $user->hydrate($data);
        $this->assertEquals($user->name, 'myname');
        $this->assertEquals($user->loginname, 'test'); // <<----- this is what the bug is about
    }
}
