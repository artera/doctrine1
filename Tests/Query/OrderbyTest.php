<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class OrderbyTest extends DoctrineUnitTestCase
{
    public function testOrderByRandomIsSupported()
    {
        $q = new \Doctrine1\Query();

        $q->select('u.name, RANDOM() rand')
            ->from('User u')
            ->orderby('rand DESC');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, ((RANDOM() + 2147483648) / 4294967296) AS e__0 FROM entity e WHERE (e.type = 0) ORDER BY e__0 DESC');
    }

    public function testOrderByAggregateValueIsSupported()
    {
        $q = new \Doctrine1\Query();

        $q->select('u.name, COUNT(p.phonenumber) count')
            ->from('User u')
            ->leftJoin('u.Phonenumber p')
            ->orderby('count DESC');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, COUNT(p.phonenumber) AS p__0 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) ORDER BY p__0 DESC');
    }

    // ticket #681
    public function testOrderByWithCoalesce()
    {
        $q = new \Doctrine1\Query();

        $q->select('u.name')
            ->from('User u')
            ->orderby('COALESCE(u.id, u.name) DESC');
        // nonsense results expected, but query is syntatically ok.
        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0) ORDER BY COALESCE(e.id, e.name) DESC');
    }

    public function testOrderByWithMultipleCoalesce()
    {
        $q = new \Doctrine1\Query();

        $q->select('u.name')
            ->from('User u')
            ->orderby('COALESCE(u.id, u.name) DESC, COALESCE(u.name, u.id) ASC');
        // nonsense results expected, but query is syntatically ok.
        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0) ORDER BY COALESCE(e.id, e.name) DESC, COALESCE(e.name, e.id) ASC');
    }

    public function testOrderByWithDifferentOrdering()
    {
        $q = new \Doctrine1\Query();

        $q->select('u.name')
            ->from('User u')
            ->orderby('u.id ASC, u.name DESC');
        // nonsense results expected, but query is syntatically ok.
        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0) ORDER BY e.id ASC, e.name DESC');
    }
}
