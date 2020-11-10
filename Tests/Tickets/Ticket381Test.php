<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket381Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['Book'];

    public function testTicket()
    {
        $obj = new \Book();
        $obj->save();
        $obj->set('name', 'yes');
        $obj->save();
        $this->assertEquals($obj->get('name'), 'yes');
        $obj->save();
    }

    public function testTicket2()
    {
        $obj = new \Book();
        $obj->set('name', 'yes2');
        $obj->save();
        $this->assertEquals($obj->get('name'), 'yes2');
        $obj->save();
    }
}
