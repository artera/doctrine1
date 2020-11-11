<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class SelectTest extends DoctrineUnitTestCase
{
    public function testParseSelect()
    {
        $q = new \Doctrine_Query();

        $q->select('TRIM(u.name) name')->from('User u');

        $q->execute();
    }

    public function testAggregateFunctionParsingSupportsMultipleComponentReferences()
    {
        $q = new \Doctrine_Query();
        $q->select("CONCAT(u.name, ' ', e.address) value")
            ->from('User u')->innerJoin('u.Email e');

        $this->assertEquals($q->getSqlQuery(), "SELECT CONCAT(e.name, ' ', e2.address) AS e__0 FROM entity e INNER JOIN email e2 ON e.email_id = e2.id WHERE (e.type = 0)");

        $users = $q->execute();
        $this->assertEquals($users[0]->value, 'zYne zYne@example.com');
    }

    public function testSelectDistinctIsSupported()
    {
        $q = new \Doctrine_Query();

        $q->distinct()->select('u.name')->from('User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT DISTINCT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0)');
    }

    public function testSelectDistinctIsSupported2()
    {
        $q = new \Doctrine_Query();

        $q->select('DISTINCT u.name')->from('User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT DISTINCT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0)');
    }

    public function testAggregateFunctionWithDistinctKeyword()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT COUNT(DISTINCT u.name) FROM User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT COUNT(DISTINCT e.name) AS e__0 FROM entity e WHERE (e.type = 0)');
    }

    public function testAggregateFunction()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT COUNT(u.id) FROM User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT COUNT(e.id) AS e__0 FROM entity e WHERE (e.type = 0)');
    }

    public function testSelectPartSupportsMultipleAggregateFunctions()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT MAX(u.id), MIN(u.name) FROM User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT MAX(e.id) AS e__0, MIN(e.name) AS e__1 FROM entity e WHERE (e.type = 0)');
    }

    public function testMultipleAggregateFunctionsWithMultipleComponents()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT MAX(u.id), MIN(u.name), COUNT(p.id) FROM User u, u.Phonenumber p');

        $this->assertEquals($q->getSqlQuery(), 'SELECT MAX(e.id) AS e__0, MIN(e.name) AS e__1, COUNT(p.id) AS p__2 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }

    public function testChangeUpdateToSelect()
    {
        $q = \Doctrine_Query::create()
            ->update('User u')
            ->set('u.password', '?', 'newpassword')
            ->where('u.username = ?', 'jwage');

        $this->assertEquals($q->getType(), \Doctrine_Query_Abstract::UPDATE);
        $this->assertEquals($q->getDql(), 'UPDATE User u SET u.password = ? WHERE u.username = ?');

        $q->select();

        $this->assertEquals($q->getType(), \Doctrine_Query_Abstract::SELECT);
        $this->assertEquals($q->getDql(), ' FROM User u WHERE u.username = ?');
    }

    public function testAggregateFunctionValueHydration()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.id, u.name, COUNT(p.id) FROM User u LEFT JOIN u.Phonenumber p GROUP BY u.id');

        $users = $q->execute([], \Doctrine_Core::HYDRATE_ARRAY);

        $this->assertEquals($users[0]['COUNT'], 1);

        $this->assertEquals($users[1]['COUNT'], 3);
        $this->assertEquals($users[2]['COUNT'], 1);
        $this->assertEquals($users[3]['COUNT'], 1);
        $this->assertEquals($users[4]['COUNT'], 3);
    }

    public function testSingleComponentWithAsterisk()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.* FROM User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }
    public function testSingleComponentWithMultipleColumns()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.name, u.type FROM User u');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.type AS e__type FROM entity e WHERE (e.type = 0)');
    }
    public function testMultipleComponentsWithAsterisk()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.*, p.* FROM User u, u.Phonenumber p');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testMultipleComponentsWithMultipleColumns()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.id, u.name, p.id FROM User u, u.Phonenumber p');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testAggregateFunctionValueHydrationWithAliases()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.id, COUNT(p.id) count FROM User u, u.Phonenumber p GROUP BY u.id');

        $users = $q->execute();

        $this->assertEquals($users[0]->count, 1);
        $this->assertEquals($users[1]->count, 3);
        $this->assertEquals($users[2]->count, 1);
        $this->assertEquals($users[3]->count, 1);
        $this->assertEquals($users[4]->count, 3);
    }
    public function testMultipleAggregateFunctionValueHydrationWithAliases()
    {
        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.id, COUNT(p.id) count, MAX(p.phonenumber) max FROM User u, u.Phonenumber p GROUP BY u.id');

        $users = $q->execute();
        $this->assertEquals($users[0]->count, 1);
        $this->assertEquals($users[1]->count, 3);
        $this->assertEquals($users[2]->count, 1);
        $this->assertEquals($users[3]->count, 1);
        $this->assertEquals($users[4]->count, 3);

        $this->assertEquals($users[0]->max, '123 123');
        $this->assertEquals($users[1]->max, '789 789');
        $this->assertEquals($users[2]->max, '123 123');
        $this->assertEquals($users[3]->max, '111 222 333');
        $this->assertEquals($users[4]->max, '444 555');
    }
    public function testMultipleAggregateFunctionValueHydrationWithAliasesAndCleanRecords()
    {
        static::$connection->clear();

        $q = new \Doctrine_Query();

        $q->parseDqlQuery('SELECT u.id, COUNT(p.id) count, MAX(p.phonenumber) max FROM User u, u.Phonenumber p GROUP BY u.id');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, COUNT(p.id) AS p__0, MAX(p.phonenumber) AS p__1 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) GROUP BY e.id');

        $users = $q->execute();

        $this->assertEquals($users[0]->count, 1);
        $this->assertEquals($users[1]->count, 3);
        $this->assertEquals($users[2]->count, 1);
        $this->assertEquals($users[3]->count, 1);
        $this->assertEquals($users[4]->count, 3);

        $this->assertEquals($users[0]->max, '123 123');
        $this->assertEquals($users[1]->max, '789 789');
        $this->assertEquals($users[2]->max, '123 123');
        $this->assertEquals($users[3]->max, '111 222 333');
        $this->assertEquals($users[4]->max, '444 555');
    }

    public function testWhereInSupportInDql()
    {
        $q = \Doctrine_Query::create()
            ->select('u.id, p.id')
            ->from('User u')
            ->leftJoin('u.Phonenumber p')
            ->where('u.id IN ?');

        $params = [[4, 5, 6]];

        $this->assertEquals(
            $q->getSqlQuery($params),
            'SELECT e.id AS e__id, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.id IN (?, ?, ?) AND (e.type = 0))'
        );

        $users = $q->execute($params, \Doctrine_Core::HYDRATE_ARRAY);

        $this->assertEquals(count($users), 3);
    }
}
