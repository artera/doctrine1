<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class RemoveQueryPartTest extends DoctrineUnitTestCase
{
    public function testQueryRemoveOrderByPart()
    {
        $q = new \Doctrine_Query();
        $q->from('User u');
        $q->orderBy('u.id DESC');

        $q->removeDqlQueryPart('orderby');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }

    public function testQueryRemoveLimitPart()
    {
        $q = new \Doctrine_Query();
        $q->from('User u');
        $q->limit(20);
        $q->removeDqlQueryPart('limit');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }

    public function testQueryRemoveOffsetPart()
    {
        $q = new \Doctrine_Query();
        $q->from('User u');
        $q->offset(10);
        $q->removeDqlQueryPart('offset');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }

    public function testQuerySetLimitToZero()
    {
        $q = new \Doctrine_Query();
        $q->from('User u');
        $q->limit(20);
        $q->limit(0);

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }

    public function testQuerySetOffsetToZero()
    {
        $q = new \Doctrine_Query();
        $q->from('User u');
        $q->offset(20);
        $q->offset(0);

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }
}
