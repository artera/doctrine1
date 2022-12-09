<?php

namespace Tests\Misc;

use Tests\DoctrineUnitTestCase;

class NewCoreTest extends DoctrineUnitTestCase
{
    public function testFromParser()
    {
        $q = new \Doctrine1\Query();

        $q->load('User u', true);

        $this->assertEquals($q->getSqlQueryPart('from'), ['entity e']);
        $this->assertEquals(count($q->getQueryComponents()), 1);

        $q->load('u.Phonenumber p', false);

        $this->assertEquals($q->getSqlQueryPart('from'), ['entity e', 'p' => 'LEFT JOIN phonenumber p ON e.id = p.entity_id']);
    }
}
