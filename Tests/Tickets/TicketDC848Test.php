<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class TicketDC848Test extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testIso8601Timestamp()
    {
        $timestamp          = '2010-03-02T22:08:56+00:00';
        $timestampValidator = \Doctrine_Validator::getValidator('timestamp');

        $this->assertTrue($timestampValidator->validate($timestamp));
    }


    public function testMysqlTimestamp()
    {
        $timestamp          = '2010-03-02 22:08:56';
        $timestampValidator = \Doctrine_Validator::getValidator('timestamp');

        $this->assertTrue($timestampValidator->validate($timestamp));
    }
}
