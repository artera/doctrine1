<?php
namespace Tests\Query {
    use Tests\DoctrineUnitTestCase;

    class QueryTest extends DoctrineUnitTestCase
    {
        public function testWhereInSupportInDql()
        {
            $q = \Doctrine_Query::create()
                ->from('User u')
                ->where('u.id IN ?', [[1, 2, 3]])
                ->whereNotIn('u.name', ['', 'a'])
                ->addWhere('u.id NOT IN ?', [[4, 5, 6, 7]]);

            $this->assertEquals(
                $q->getSqlQuery(),
                'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id IN (?, ?, ?) AND e.name NOT IN (?, ?) AND e.id NOT IN (?, ?, ?, ?) AND (e.type = 0))'
            );
        }


        public function testWhereInSupportInDql2()
        {
            $q = \Doctrine_Query::create()
                ->from('User u')
                ->where('u.id IN ?', [1]);

            $this->assertEquals(
                $q->getSqlQuery(),
                'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id IN (?) AND (e.type = 0))'
            );
        }


        public function testGetQueryHookResetsTheManuallyAddedDqlParts()
        {
            $q = new \MyQuery();
            $q->from('User u');

            $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id = 4 AND (e.type = 0))');

            // test consequent calls
            $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.id = 4 AND (e.type = 0))');
        }


        public function testParseClauseSupportsArithmeticOperators()
        {
            $q = new \Doctrine_Query();

            $str = $q->parseClause('2 + 3');

            $this->assertEquals($str, '2 + 3');

            $str = $q->parseClause('2 + 3 - 5 * 6');

            $this->assertEquals($str, '2 + 3 - 5 * 6');
        }
        public function testParseClauseSupportsArithmeticOperatorsWithFunctions()
        {
            $q = new \Doctrine_Query();

            $str = $q->parseClause('ACOS(2) + 3');

            $this->assertEquals($str, 'ACOS(2) + 3');
        }

        public function testParseClauseSupportsArithmeticOperatorsWithParenthesis()
        {
            $q = new \Doctrine_Query();

            $str = $q->parseClause('(3 + 3)*3');

            $this->assertEquals($str, '(3 + 3)*3');

            $str = $q->parseClause('((3 + 3)*3 - 123) * 12 * (13 + 31)');

            $this->assertEquals($str, '((3 + 3)*3 - 123) * 12 * (13 + 31)');
        }

        public function testParseClauseSupportsArithmeticOperatorsWithParenthesisAndFunctions()
        {
            $q = new \Doctrine_Query();

            $str = $q->parseClause('(3 + 3)*ACOS(3)');

            $this->assertEquals($str, '(3 + 3)*ACOS(3)');

            $str = $q->parseClause('((3 + 3)*3 - 123) * ACOS(12) * (13 + 31)');

            $this->assertEquals($str, '((3 + 3)*3 - 123) * ACOS(12) * (13 + 31)');
        }

        public function testParseClauseSupportsComponentReferences()
        {
            $q = new \Doctrine_Query();
            $q->from('User u')->leftJoin('u.Phonenumber p');
            $q->getSqlQuery();
            $this->assertEquals($q->parseClause("CONCAT('u.name', u.name)"), "CONCAT('u.name', e.name)");
        }

        public function testCountMaintainsParams()
        {
            $q = new \Doctrine_Query();
            $q->from('User u');
            $q->leftJoin('u.Phonenumber p');
            $q->where('u.name = ?', 'zYne');

            $this->assertEquals($q->count(), $q->execute()->count());
        }

        public function testCountWithGroupBy()
        {
            $q = new \Doctrine_Query();
            $q->from('User u');
            $q->leftJoin('u.Phonenumber p');
            $q->groupBy('p.entity_id');

            $this->assertEquals($q->count(), $q->execute()->count());
        }

        // ticket #821
        public function testQueryCopyClone()
        {
            $query = new \Doctrine_Query();
            $query->select('u.*')->from('User u');
            $sql = $query->getSqlQuery();

            $data   = $query->execute();
            $query2 = clone $query;

            $this->assertEquals($sql, $query2->getSqlQuery());

            $query2->limit(0);
            $query2->offset(0);
            $query2->select('COUNT(u.id) as nb');

            $this->assertEquals($query2->getSqlQuery(), 'SELECT COUNT(e.id) AS e__0 FROM entity e WHERE (e.type = 0)');
        }

        public function testNullAggregateIsSet()
        {
            $user                              = new \User();
            $user->name                        = 'jon';
            $user->loginname                   = 'jwage';
            $user->Phonenumber[0]->phonenumber = new \Doctrine_Expression('NULL');
            $user->save();
            $id = $user->id;
            $user->free();

            $query = \Doctrine_Query::create()
                ->select('u.*, p.*, SUM(p.phonenumber) summ')
                ->from('User u')
                ->leftJoin('u.Phonenumber p')
                ->where('u.id = ?', $id);

            $users = $query->execute([], \Doctrine_Core::HYDRATE_ARRAY);

            $this->assertTrue(array_key_exists('summ', $users[0]));
        }

        public function testQueryWithNoSelectFromRootTableThrowsException()
        {
            $this->expectException(\Doctrine_Query_Exception::class);
            $users = \Doctrine_Query::create()
                ->select('p.*')
                ->from('User u')
                ->leftJoin('u.Phonenumber p')
                ->execute();
        }


        public function testOrQuerySupport()
        {
            $q1 = \Doctrine_Query::create()
                ->select('u.id')
                ->from('User u')
                ->leftJoin('u.Phonenumber p')
                ->where('u.name = ?')
                ->orWhere('u.loginname = ?');

            $q2 = \Doctrine_Query::create()
                ->select('u.id')
                ->from('User u')
                ->leftJoin('u.Phonenumber p')
                ->where('u.name = ? OR u.loginname = ?');

            $this->assertEquals(
                $q1->getSqlQuery(),
                'SELECT e.id AS e__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id ' .
                'WHERE (e.name = ? OR e.loginname = ? AND (e.type = 0))'
            );

            $items1 = $q1->execute(['zYne', 'jwage'], \Doctrine_Core::HYDRATE_ARRAY);
            $items2 = $q2->execute(['zYne', 'jwage'], \Doctrine_Core::HYDRATE_ARRAY);

            $this->assertEquals(count($items1), count($items2));

            $q1->free();
            $q2->free();
        }


        public function testOrQuerySupport2()
        {
            $q1 = \Doctrine_Query::create()
                ->select('u.id')
                ->from('User u')
                ->leftJoin('u.Phonenumber p')
                ->where('u.name = ?')
                ->andWhere('u.loginname = ?')
                ->orWhere('u.id = ?');

            $q2 = \Doctrine_Query::create()
                ->select('u.id')
                ->from('User u')
                ->leftJoin('u.Phonenumber p')
                ->where('(u.name = ? AND u.loginname = ?) OR (u.id = ?)');

            $this->assertEquals(
                $q1->getSqlQuery(),
                'SELECT e.id AS e__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id ' .
                'WHERE (e.name = ? AND e.loginname = ? OR e.id = ? AND (e.type = 0))'
            );

            $items1 = $q1->execute(['jon', 'jwage', 4], \Doctrine_Core::HYDRATE_ARRAY);
            $items2 = $q2->execute(['jon', 'jwage', 4], \Doctrine_Core::HYDRATE_ARRAY);

            $this->assertEquals(count($items1), count($items2));

            $q1->free();
            $q2->free();
        }


        public function testOrQuerySupport3()
        {
            $q1 = \Doctrine_Query::create()
                ->select('u.id')
                ->from('User u')
                ->leftJoin('u.Phonenumber p')
                ->where("u.name = 'jon'")
                ->andWhere("u.loginname = 'jwage'")
                ->orWhere('u.id = 4')
                ->orWhere('u.id = 5')
                ->andWhere("u.name LIKE 'Arnold%'");

            $q2 = \Doctrine_Query::create()
                ->select('u.id')
                ->from('User u')
                ->leftJoin('u.Phonenumber p')
                ->where("((u.name = 'jon' AND u.loginname = 'jwage') OR (u.id = 4 OR (u.id = 5 AND u.name LIKE 'Arnold%')))");

            $this->assertEquals(
                $q1->getSqlQuery(),
                'SELECT e.id AS e__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id ' .
                "WHERE (e.name = 'jon' AND e.loginname = 'jwage' OR e.id = 4 OR e.id = 5 AND e.name LIKE 'Arnold%' AND (e.type = 0))"
            );

            $items1 = $q1->execute([], \Doctrine_Core::HYDRATE_ARRAY);
            $items2 = $q2->execute([], \Doctrine_Core::HYDRATE_ARRAY);

            $this->assertEquals(count($items1), count($items2));

            $q1->free();
            $q2->free();
        }

        public function testParseTableAliasesWithBetweenInWhereClause()
        {
            $q1 = \Doctrine_Query::create()
                ->select('u.id')
                ->from('QueryTest_User u')
                ->where('CURRENT_DATE() BETWEEN u.QueryTest_Subscription.begin AND u.QueryTest_Subscription.begin')
                ->addWhere('u.id != 5');

            $expected = 'SELECT q.id AS q__id FROM query_test__user q LEFT JOIN query_test__subscription q2 ON q.subscriptionid = q2.id WHERE (CURRENT_DATE() BETWEEN q2.begin AND q2.begin AND q.id != 5)';

            $this->assertEquals($q1->getSqlQuery(), $expected);
        }


        public function testQuoteAndBracketUsageAsValueInQuery()
        {
            $q = \Doctrine_Query::create()
                ->select('u.id')
                ->from('User u')
                ->where("u.name = 'John O\'Connor (West)'");

            $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE (e.name = 'John O\'Connor (West)' AND (e.type = 0))");
        }

        public function testAsAndBracketUsageAsValueInQuery()
        {
            $q = \Doctrine_Query::create()
                ->select('u.id')
                ->from('User u')
                ->where("u.name = 'Total Kjeldahl Nitrogen (TKN) as N'");

            $this->assertEquals($q->getSqlQuery(), "SELECT e.id AS e__id FROM entity e WHERE (e.name = 'Total Kjeldahl Nitrogen (TKN) as N' AND (e.type = 0))");
        }

        public function testSetQueryClassManagerAttribute()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_QUERY_CLASS, 'MyQuery');

            $q = \Doctrine_Query::create();
            $this->assertTrue($q instanceof \MyQuery);

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_QUERY_CLASS, 'Doctrine_Query');
        }

        public function testSetQueryClassConnectionAttribute()
        {
            $userTable = \Doctrine_Core::getTable('User');
            $userTable->getConnection()->setAttribute(\Doctrine_Core::ATTR_QUERY_CLASS, 'MyQuery');

            $q = $userTable->createQuery();
            $this->assertTrue($q instanceof \MyQuery);

            $userTable->getConnection()->setAttribute(\Doctrine_Core::ATTR_QUERY_CLASS, 'Doctrine_Query');
        }

        public function testSetQueryClassTableAttribute()
        {
            $userTable = \Doctrine_Core::getTable('User');
            $userTable->setAttribute(\Doctrine_Core::ATTR_QUERY_CLASS, 'MyQuery');

            $q = $userTable->createQuery();
            $this->assertTrue($q instanceof \MyQuery);

            $userTable->setAttribute(\Doctrine_Core::ATTR_QUERY_CLASS, 'Doctrine_Query');
        }

        public function testNoLimitSubqueryIfXToOneSelected()
        {
            $q = \Doctrine_Query::create()
                ->select('u.name, e.address')
                ->from('User u')
                ->leftJoin('u.Email e')
                ->leftJoin('u.Phonenumber p')
                ->distinct()
                ->limit(1);

            $this->assertEquals($q->getSqlQuery(), 'SELECT DISTINCT e.id AS e__id, e.name AS e__name, e2.id AS e2__id, e2.address AS e2__address FROM entity e LEFT JOIN email e2 ON e.email_id = e2.id LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) LIMIT 1');
        }
    }
}

namespace {
    class MyQuery extends Doctrine_Query
    {
        public function preQuery()
        {
            $this->where('u.id = 4');
        }
    }
}
