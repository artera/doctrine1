<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket927Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
        $oEmail          = new \Email;
        $oEmail->address = 'david.stendardi@adenclassifieds.com';
        $oEmail->save();
    }

    public static function prepareTables(): void
    {
        static::$tables   = [];
        static::$tables[] = 'Email';

        parent :: prepareTables();
    }

    public function testTicket()
    {
        $q = new \Doctrine_Query();

        // simple query with deep relations
            $q->update('Email')
                ->set('address', '?', 'new@doctrine.org')
                ->where('address = ?', 'david.stendardi@adenclassifieds.com')
                ->execute();
    }
}
