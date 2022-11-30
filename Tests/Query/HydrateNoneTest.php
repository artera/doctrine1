<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class HydrateNoneTest extends DoctrineUnitTestCase
{
    public function testCheckParserSupportsStandardFunctions()
    {
        $q   = \Doctrine1\Query::create();
        $res = $q->select('u.name')->from('User u')->execute([], \Doctrine1\Core::HYDRATE_NONE);
        foreach ($res as $row) {
            $this->assertEquals(1, count($row)); // just 1 column, the name
        }
    }
}
