<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class AggregateValueTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testInitData()
    {
        $users = new \Doctrine_Collection('User');

        $users[0]->name                        = 'John';
        $users[0]->Phonenumber[0]->phonenumber = '123 123';
        $users[0]->Phonenumber[1]->phonenumber = '222 222';
        $users[0]->Phonenumber[2]->phonenumber = '333 333';

        $users[1]->name                        = 'John';
        $users[2]->name                        = 'James';
        $users[2]->Phonenumber[0]->phonenumber = '222 344';
        $users[2]->Phonenumber[1]->phonenumber = '222 344';
        $users[3]->name                        = 'James';
        $users[3]->Phonenumber[0]->phonenumber = '123 123';

        $users->save();
    }

    public function testRecordSupportsValueMapping()
    {
        $record = new \User();

        $this->expectException(\Doctrine_Record_UnknownPropertyException::class);
        $record->get('count');

        $record->mapValue('count', 3);

        $i = $record->get('count');

        $this->assertEquals($i, 3);
    }

    public function testAggregateValueIsMappedToNewRecordOnEmptyResultSet()
    {
        static::$connection->clear();

        $q = new \Doctrine_Query();

        $q->select('COUNT(u.id) count')->from('User u');
        $this->assertEquals($q->getSqlQuery(), 'SELECT COUNT(e.id) AS e__0 FROM entity e WHERE (e.type = 0)');

        $users = $q->execute();

        $this->assertEquals(count($users), 1);

        $this->assertEquals($users[0]->state(), \Doctrine_Record_State::TCLEAN());
    }

    public function testAggregateValueIsMappedToRecord()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name, COUNT(u.id) count')->from('User u')->groupby('u.name');

        $users = $q->execute();

        $this->assertEquals($users->count(), 2);

        $this->assertEquals($users[0]->state(), \Doctrine_Record_State::PROXY());
        $this->assertEquals($users[1]->state(), \Doctrine_Record_State::PROXY());

        $this->assertEquals($users[0]->count, 2);
        $this->assertEquals($users[1]->count, 2);
    }

    public function testAggregateOrder()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name, COUNT(u.id) count')->from('User u')->groupby('u.name')->orderby('count');

        $users = $q->execute();

        $this->assertEquals($users->count(), 2);

        $this->assertEquals($users[0]->state(), \Doctrine_Record_State::PROXY());
        $this->assertEquals($users[1]->state(), \Doctrine_Record_State::PROXY());

        $this->assertEquals($users[0]->count, 2);
        $this->assertEquals($users[1]->count, 2);
    }

    public function testAggregateValueMappingSupportsLeftJoins()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name, COUNT(p.id) count')->from('User u')->leftJoin('u.Phonenumber p')->groupby('u.id');

        $users = $q->execute();

        $this->assertEquals(count($users), 4);

        $this->assertEquals($users[0]['count'], 3);
        $this->assertEquals($users[1]['count'], 0);
        $this->assertEquals($users[2]['count'], 2);
        $this->assertEquals($users[3]['count'], 1);
    }

    public function testAggregateValueMappingSupportsLeftJoins2()
    {
        $q = new \Doctrine_Query();

        $q->select('MAX(u.name), u.*, p.*')->from('User u')->leftJoin('u.Phonenumber p')->groupby('u.id');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id, MAX(e.name) AS e__0 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) GROUP BY e.id');
        $users = $q->execute();

        $this->assertEquals($users->count(), 4);
    }

    public function testAggregateValueMappingSupportsMultipleValues()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name, COUNT(p.id) count, MAX(p.id) max')->from('User u')->innerJoin('u.Phonenumber p')->groupby('u.id');

        $users = $q->execute();
        $this->assertEquals($users[0]->max, 3);
        $this->assertEquals($users[0]->count, 3);
    }

    public function testAggregateValueMappingSupportsMultipleValues2()
    {
        $q = new \Doctrine_Query();

        $q->select('COUNT(u.id) count, MAX(p.id) max')->from('User u')->innerJoin('u.Phonenumber p')->groupby('u.id');

        $users = $q->execute();

        $this->assertEquals($users[0]['max'], 3);
        $this->assertEquals($users[0]['count'], 3);
    }

    public function testAggregateValueMappingSupportsInnerJoins()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name, COUNT(p.id) count')->from('User u')->innerJoin('u.Phonenumber p')->groupby('u.id');

        $users = $q->execute();

        $this->assertEquals($users->count(), 3);

        $this->assertEquals($users[0]->count, 3);
        $this->assertEquals($users[1]->count, 2);
        $this->assertEquals($users[2]->count, 1);
    }
    public function testAggregateFunctionParser()
    {
        $q = new \Doctrine_Query();
        $q->select('SUM(i.price)')->from('QueryTest_Item i');

        $this->assertEquals($q->getSqlQuery(), 'SELECT SUM(q.price) AS q__0 FROM query_test__item q');
    }
    public function testAggregateFunctionParser2()
    {
        $q = new \Doctrine_Query();
        $q->select('SUM(i.price * i.quantity)')->from('QueryTest_Item i');

        $this->assertEquals($q->getSqlQuery(), 'SELECT SUM(q.price * q.quantity) AS q__0 FROM query_test__item q');
    }
    public function testAggregateFunctionParser3()
    {
        $q = new \Doctrine_Query();
        $q->select('MOD(i.price, i.quantity)')->from('QueryTest_Item i');

        $this->assertEquals($q->getSqlQuery(), 'SELECT MOD(q.price, q.quantity) AS q__0 FROM query_test__item q');
    }
    public function testAggregateFunctionParser4()
    {
        $q = new \Doctrine_Query();
        $q->select('CONCAT(i.price, i.quantity)')->from('QueryTest_Item i');

        $this->assertEquals($q->getSqlQuery(), 'SELECT CONCAT(q.price, q.quantity) AS q__0 FROM query_test__item q');
    }
    public function testAggregateFunctionParsingSupportsMultipleComponentReferences()
    {
        $q = new \Doctrine_Query();
        $q->select('SUM(i.price * i.quantity)')
            ->from('QueryTest_Item i');

        $this->assertEquals($q->getSqlQuery(), 'SELECT SUM(q.price * q.quantity) AS q__0 FROM query_test__item q');
    }
}
