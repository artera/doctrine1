<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket583Test extends DoctrineUnitTestCase
{
    protected static array $tables = ['Entity'];

    public static function prepareData(): void
    {
    }

    public function testBug()
    {
        $entity       = new \Entity();
        $entity->name = 'myname';
        $entity->save();

        // load our user and our collection of pages
        $user = \Doctrine1\Query::create()->select('id')->from('Entity')->fetchOne();
        $this->assertEquals($user->name, 'myname');

        // load our user and our collection of pages
        $user = \Doctrine1\Query::create()->select('*')->from('Entity')->fetchOne();
        $this->assertEquals($user->name, 'myname');
    }
}
