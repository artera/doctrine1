<?php

namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1461Test extends DoctrineUnitTestCase
{
    public function testFetchArraySupportsTwoAggregates()
    {
        $q = new \Doctrine1\Query();

        $q->select("u.*, p.*, CONCAT(u.name, '_1') concat1, CONCAT(u.name, '_2') concat2")
            ->from('User u')
            ->innerJoin('u.Phonenumber p')
            ->where("u.name = 'zYne'");

        $users = $q->execute([], \Doctrine1\HydrationMode::Array);

        $this->assertEquals($users[0]['concat1'], 'zYne_1');

        $this->assertEquals($users[0]['concat2'], 'zYne_2');
    }

    public function testFetchArraySupportsTwoAggregatesInRelation()
    {
        $q = new \Doctrine1\Query();

        $q->select("u.*, p.*, CONCAT(p.phonenumber, '_1') concat1, CONCAT(p.phonenumber, '_2') concat2")
            ->from('User u')
            ->innerJoin('u.Phonenumber p')
            ->where("u.name = 'zYne'");

        $users = $q->execute([], \Doctrine1\HydrationMode::Array);

        $this->assertEquals($users[0]['concat2'], '123 123_2');

        $this->assertEquals($users[0]['concat1'], '123 123_1');
    }

    public function testFetchArraySupportsTwoAggregatesInRelationAndRoot()
    {
        $q = new \Doctrine1\Query();

        $q->select("u.*, p.*, CONCAT(u.name, '_1') concat1, CONCAT(u.name, '_2') concat2, CONCAT(p.phonenumber, '_3') concat3, CONCAT(p.phonenumber, '_3') concat4")
            ->from('User u')
            ->innerJoin('u.Phonenumber p')
            ->where("u.name = 'zYne'");

        $users = $q->execute([], \Doctrine1\HydrationMode::Array);

        $this->assertEquals($users[0]['concat1'], 'zYne_1');

        $this->assertEquals($users[0]['concat2'], 'zYne_2');

        $this->assertEquals($users[0]['concat3'], '123 123_3');

        $this->assertTrue(isset($users[0]['concat4']));
    }
}
