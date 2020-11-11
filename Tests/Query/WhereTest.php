<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class WhereTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['Entity', 'EnumTest', 'GroupUser', 'Account', 'Book'];

    public function testDirectParameterSetting()
    {
        static::$connection->clear();

        $user       = new \User();
        $user->name = 'someone';
        $user->save();

        $q = new \Doctrine_Query();

        $q->from('User')->addWhere('User.id = ?', 1);

        $users = $q->execute();

        $this->assertEquals($users->count(), 1);
        $this->assertEquals($users[0]->name, 'someone');
    }

    public function testFunctionalExpressionAreSupportedInWherePart()
    {
        $q = new \Doctrine_Query();

        $q->select('u.name')->from('User u')->addWhere('TRIM(u.name) = ?', 'someone');

        $users = $q->execute();

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e WHERE (TRIM(e.name) = ? AND (e.type = 0))');
        $this->assertEquals($users->count(), 1);
        $this->assertEquals($users[0]->name, 'someone');
    }

    public function testArithmeticExpressionAreSupportedInWherePart()
    {
        static::$connection->clear();

        $account         = new \Account();
        $account->amount = 1000;
        $account->save();

        $q = new \Doctrine_Query();

        $q->from('Account a')->addWhere('((a.amount + 5000) * a.amount + 3) < 10000000');

        $accounts = $q->execute();

        $this->assertEquals($q->getSqlQuery(), 'SELECT a.id AS a__id, a.entity_id AS a__entity_id, a.amount AS a__amount FROM account a WHERE (((a.amount + 5000) * a.amount + 3) < 10000000)');
        $this->assertEquals($accounts->count(), 1);
        $this->assertEquals($accounts[0]->amount, 1000);
    }

    public function testDirectMultipleParameterSetting()
    {
        $user       = new \User();
        $user->name = 'someone.2';
        $user->save();

        $q = new \Doctrine_Query();

        $q->from('User')->addWhere('User.id IN (?, ?)', [1, 2]);

        $users = $q->execute();

        $this->assertEquals($users->count(), 2);
        $this->assertEquals($users[0]->name, 'someone');
        $this->assertEquals($users[1]->name, 'someone.2');
    }

    public function testExceptionIsThrownWhenParameterIsNull()
    {
        $this->expectException(\Doctrine_Query_Exception::class);
        \Doctrine_Query::create()->delete('User')->whereIn('User.id', null)->execute();
    }

    public function testDirectMultipleParameterSetting2()
    {
        $q = \Doctrine_Query::create()
            ->from('User')
            ->where('User.id IN (?, ?)', [1, 2]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id IN (?, ?) AND (e.type = 0))');

        $users = $q->execute();

        $this->assertEquals($users->count(), 2);
        $this->assertEquals($users[0]->name, 'someone');
        $this->assertEquals($users[1]->name, 'someone.2');

        // the parameters and where part should be reseted
        $q->where('User.id IN (?, ?)', [1, 2]);

        $users = $q->execute();

        $this->assertEquals($users->count(), 2);
        $this->assertEquals($users[0]->name, 'someone');
        $this->assertEquals($users[1]->name, 'someone.2');
    }

    public function testNotInExpression()
    {
        $q = new \Doctrine_Query();

        $q->from('User u')->addWhere('u.id NOT IN (?)', [1]);
        $users = $q->execute();

        $this->assertEquals($users->count(), 1);
        $this->assertEquals($users[0]->name, 'someone.2');
    }

    public function testExistsExpression()
    {
        $q = new \Doctrine_Query();

        $user                 = new \User();
        $user->name           = 'someone with a group';
        $user->Group[0]->name = 'some group';
        $user->save();

        // find all users which have groups
        $q->from('User u')->where('EXISTS (SELECT g.id FROM GroupUser g WHERE g.user_id = u.id)');

        $users = $q->execute();

        $this->assertEquals($users->count(), 1);
        $this->assertEquals($users[0]->name, 'someone with a group');
    }

    public function testNotExistsExpression()
    {
        $q = new \Doctrine_Query();

        // find all users which don't have groups
        $q->from('User u')->where('NOT EXISTS (SELECT GroupUser.id FROM GroupUser WHERE GroupUser.user_id = u.id)');

        $users = $q->execute();
        $this->assertEquals($users->count(), 2);
        $this->assertEquals($users[0]->name, 'someone');
        $this->assertEquals($users[1]->name, 'someone.2');
    }

    public function testComponentAliases()
    {
        $q = new \Doctrine_Query();

        $q->from('User u')->addWhere('u.id IN (?, ?)', [1,2]);

        $users = $q->execute();

        $this->assertEquals($users->count(), 2);
        $this->assertEquals($users[0]->name, 'someone');
        $this->assertEquals($users[1]->name, 'someone.2');
    }

    public function testComponentAliases2()
    {
        $q = new \Doctrine_Query();

        $q->from('User u')->addWhere('u.name = ?', ['someone']);

        $users = $q->execute();

        $this->assertEquals($users->count(), 1);
        $this->assertEquals($users[0]->name, 'someone');
    }

    public function testOperatorWithNoTrailingSpaces()
    {
        $q = new \Doctrine_Query();

        $q->select('User.id')->from('User')->where("User.name='someone'");

        $users = $q->execute();
        $this->assertEquals($users->count(), 1);

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE (e.name = 'someone' AND (e.type = 0))");
    }

    public function testOperatorWithNoTrailingSpaces2()
    {
        $q = new \Doctrine_Query();

        $q->select('User.id')->from('User')->where("User.name='foo.bar'");

        $users = $q->execute();
        $this->assertEquals($users->count(), 0);

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE (e.name = 'foo.bar' AND (e.type = 0))");
    }

    public function testOperatorWithSingleTrailingSpace()
    {
        $q = new \Doctrine_Query();

        $q->select('User.id')->from('User')->where("User.name= 'foo.bar'");

        $users = $q->execute();
        $this->assertEquals($users->count(), 0);

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE (e.name = 'foo.bar' AND (e.type = 0))");
    }

    public function testOperatorWithSingleTrailingSpace2()
    {
        $q = new \Doctrine_Query();

        $q->select('User.id')->from('User')->where("User.name ='foo.bar'");

        $users = $q->execute();
        $this->assertEquals($users->count(), 0);

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE (e.name = 'foo.bar' AND (e.type = 0))");
    }

    public function testDeepComponentReferencingIsSupported()
    {
        $q = new \Doctrine_Query();

        $q->select('u.id')->from('User u')->where("u.Group.name ='some group'");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 WHERE (e2.name = 'some group' AND (e.type = 0))");
    }

    public function testDeepComponentReferencingIsSupported2()
    {
        $q = new \Doctrine_Query();

        $q->select('u.id')->from('User u')->addWhere("u.Group.name ='some group'");

        $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 WHERE (e2.name = 'some group' AND (e.type = 0))");
    }

    public function testLiteralValueAsInOperatorOperandIsSupported()
    {
        $q = new \Doctrine_Query();

        $q->select('u.id')->from('User u')->where('1 IN (1, 2)');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id FROM entity e WHERE (1 IN (1, 2) AND (e.type = 0))');
    }

    public function testCorrelatedSubqueryWithInOperatorIsSupported()
    {
        $q = new \Doctrine_Query();

        $q->select('u.id')->from('User u')->where('u.name IN (SELECT u2.name FROM User u2 WHERE u2.id = u.id)');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id FROM entity e WHERE (e.name IN (SELECT e2.name AS e2__name FROM entity e2 WHERE (e2.id = e.id AND (e2.type = 0))) AND (e.type = 0))');
    }
}
