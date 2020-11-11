<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class CopyTest extends DoctrineUnitTestCase
{
    public function testQueryCopy()
    {
        $q = new \Doctrine_Query();

        $q->from('User u');

        $q2 = $q->copy();

        $this->assertEquals($q->getSqlQuery(), $q2->getSqlQuery());

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }
}
