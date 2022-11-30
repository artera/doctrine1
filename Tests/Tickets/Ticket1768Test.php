<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1768Test extends DoctrineUnitTestCase
{
    public function testResultCacheHashShouldProduceDifferentHashesWhenPassingParamsToWhereMethod()
    {
        $queryOne = \Doctrine1\Query::create()
            ->from('Ticket_1768_Foo f')
            ->where('f.bar = ?', 1);

        $queryTwo = \Doctrine1\Query::create()
            ->from('Ticket_1768_Foo f')
            ->where('f.bar = ?', 2);

        //Result hashes should be different
        $this->assertNotEquals($queryOne->calculateResultCacheHash(), $queryTwo->calculateResultCacheHash());
    }
}
