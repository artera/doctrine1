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
        $timestampValidator = \Doctrine1\Validator::getValidator('timestamp');

        $this->assertTrue($timestampValidator->isValid($timestamp));
    }


    public function testMysqlTimestamp()
    {
        $timestamp          = '2010-03-02 22:08:56';
        $timestampValidator = \Doctrine1\Validator::getValidator('timestamp');

        $this->assertTrue($timestampValidator->isValid($timestamp));
    }
}
