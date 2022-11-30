<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class JoinTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['Record_Country', 'Record_City', 'Record_District', 'Entity',
                              'User', 'Group', 'Email', 'Phonenumber', 'GroupUser', 'Account'];
    public static function prepareData(): void
    {
    }

    public function testInitData(): void
    {
        $c = new \Record_Country();

        $c->name = 'Some country';

        $c->City[0]->name = 'City 1';
        $c->City[1]->name = 'City 2';
        $c->City[2]->name = 'City 3';

        $c->City[0]->District = new \Record_District;
        $c->City[0]->District->name = 'District 1';
        $c->City[2]->District = new \Record_District;
        $c->City[2]->District->name = 'District 2';

        $this->assertEquals(gettype($c->City[0]->District), 'object');
        $this->assertEquals(gettype($c->City[0]->District->name), 'string');

        $c->save();

        static::$connection->clear();
    }

    public function testQuerySupportsCustomJoins(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')->innerJoin('c.City c2 ON c2.id = 2')
            ->where('c.id = ?', [1]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id FROM record__country r INNER JOIN record__city r2 ON (r2.id = 2) WHERE (r.id = ?)');
    }


    public function testQueryAggFunctionInJoins(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')
            ->innerJoin('c.City c2 WITH LOWER(c2.name) LIKE LOWER(?)', ['city 1'])
            ->where('c.id = ?', [1]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id FROM record__country r INNER JOIN record__city r2 ON r.id = r2.country_id AND (LOWER(r2.name) LIKE LOWER(?)) WHERE (r.id = ?)');
    }

    public function testSubQueryInJoins(): void
    {
        $q = new \Doctrine1\Query();
        $q->from('Record_Country c')
            ->innerJoin('c.City c2 WITH (c2.name = ? OR c2.id IN (SELECT c3.id FROM Record_City c3 WHERE c3.id = ? OR c3.id = ?))');
        $sql = $q->getSqlQuery();
        $this->assertEquals($sql, 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id FROM record__country r INNER JOIN record__city r2 ON r.id = r2.country_id AND ((r2.name = ? OR r2.id IN (SELECT r3.id AS r3__id FROM record__city r3 WHERE (r3.id = ? OR r3.id = ?))))');
    }

    public function testQueryMultipleAggFunctionInJoins(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')
            ->innerJoin('c.City c2 WITH LOWER(UPPER(c2.name)) LIKE LOWER(?)', ['city 1'])
            ->where('c.id = ?', [1]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id FROM record__country r INNER JOIN record__city r2 ON r.id = r2.country_id AND (LOWER(UPPER(r2.name)) LIKE LOWER(?)) WHERE (r.id = ?)');
    }


    public function testQueryMultipleAggFunctionInJoins2(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')
            ->innerJoin('c.City c2 WITH LOWER(UPPER(c2.name)) LIKE CONCAT(UPPER(?), UPPER(c2.name))', ['city 1'])
            ->where('c.id = ?', [1]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id FROM record__country r INNER JOIN record__city r2 ON r.id = r2.country_id AND (LOWER(UPPER(r2.name)) LIKE CONCAT(UPPER(?), UPPER(r2.name))) WHERE (r.id = ?)');
    }


    public function testQueryMultipleAggFunctionInJoins3(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')
            ->innerJoin('c.City c2 WITH CONCAT(UPPER(c2.name), c2.name) LIKE UPPER(?)', ['CITY 1city 1'])
            ->where('c.id = ?', [1]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id FROM record__country r INNER JOIN record__city r2 ON r.id = r2.country_id AND (CONCAT(UPPER(r2.name), r2.name) LIKE UPPER(?)) WHERE (r.id = ?)');
    }


    public function testQueryWithInInsideJoins(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')
            ->innerJoin('c.City c2 WITH c2.id IN (?, ?)', [1, 2])
            ->where('c.id = ?', [1]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id FROM record__country r INNER JOIN record__city r2 ON r.id = r2.country_id AND (r2.id IN (?, ?)) WHERE (r.id = ?)');
    }


    public function testQuerySupportsCustomJoinsAndWithKeyword(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')->innerJoin('c.City c2 WITH c2.id = 2')
            ->where('c.id = ?', [1]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id FROM record__country r INNER JOIN record__city r2 ON r.id = r2.country_id AND (r2.id = 2) WHERE (r.id = ?)');
    }

    /**
     * @depends testInitData
     */
    public function testRecordHydrationWorksWithDeeplyNestedStructuresAndArrayFetching(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')->leftJoin('c.City c2')->leftJoin('c2.District d')
            ->where('c.id = ?', [1]);

        $countries = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY);

        $c = $countries[0];
        $this->assertEquals($c['City'][0]['name'], 'City 1');
        $this->assertEquals($c['City'][1]['name'], 'City 2');
        $this->assertEquals($c['City'][2]['name'], 'City 3');

        $this->assertEquals($c['City'][0]['District']['name'], 'District 1');
        $this->assertEquals($c['City'][2]['District']['name'], 'District 2');
    }

    /**
     * @depends testInitData
     */
    public function testRecordHydrationWorksWithDeeplyNestedStructures(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('c.*, c2.*, d.*')
            ->from('Record_Country c')->leftJoin('c.City c2')->leftJoin('c2.District d')
            ->where('c.id = ?', [1]);

        $this->assertEquals($q->getSqlQuery(), 'SELECT r.id AS r__id, r.name AS r__name, r2.id AS r2__id, r2.name AS r2__name, r2.country_id AS r2__country_id, r2.district_id AS r2__district_id, r3.id AS r3__id, r3.name AS r3__name FROM record__country r LEFT JOIN record__city r2 ON r.id = r2.country_id LEFT JOIN record__district r3 ON r2.district_id = r3.id WHERE (r.id = ?)');

        $countries = $q->execute();

        $c = $countries[0];
        $this->assertEquals($c->City[0]->name, 'City 1');
        $this->assertEquals($c->City[1]->name, 'City 2');
        $this->assertEquals($c->City[2]->name, 'City 3');

        $this->assertEquals($c->City[0]->District->name, 'District 1');
        $this->assertEquals($c->City[2]->District->name, 'District 2');
    }

    public function testManyToManyJoinUsesProperTableAliases(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('u.name')->from('User u INNER JOIN u.Group g');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e INNER JOIN group_user g ON (e.id = g.user_id) INNER JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 WHERE (e.type = 0)');
    }

    public function testSelfReferentialAssociationJoinsAreSupported(): void
    {
        $q = new \Doctrine1\Query();

        $q->select('e.name')->from('Entity e INNER JOIN e.Entity e2');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e INNER JOIN entity_reference e3 ON (e.id = e3.entity1 OR e.id = e3.entity2) INNER JOIN entity e2 ON (e2.id = e3.entity2 OR e2.id = e3.entity1) AND e2.id != e.id');
    }

    public function testMultipleJoins(): void
    {
        $q = new \Doctrine1\Query();
        $q->select('u.id, g.id, e.id')->from('User u')
            ->leftJoin('u.Group g')->leftJoin('g.Email e');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e2.id AS e2__id, e3.id AS e3__id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 LEFT JOIN email e3 ON e2.email_id = e3.id WHERE (e.type = 0)');
        $q->execute();
    }

    public function testMultipleJoins2(): void
    {
        $q = new \Doctrine1\Query();
        $q->select('u.id, g.id, e.id')->from('Group g')
            ->leftJoin('g.User u')->leftJoin('u.Account a');

        $this->assertEquals($q->getSqlQuery(), 'SELECT e.id AS e__id, e2.id AS e2__id FROM entity e LEFT JOIN group_user g ON (e.id = g.group_id) LEFT JOIN entity e2 ON e2.id = g.user_id AND e2.type = 0 LEFT JOIN account a ON e2.id = a.entity_id WHERE (e.type = 1)');
        $q->execute();
    }

    /**
     * @depends testInitData
     */
    public function testMapKeywordForQueryWithOneComponent(): void
    {
        $q    = new \Doctrine1\Query();
        $coll = $q->from('Record_City c INDEXBY c.name')->fetchArray();

        $this->assertTrue(isset($coll['City 1']));
        $this->assertTrue(isset($coll['City 2']));
        $this->assertTrue(isset($coll['City 3']));
    }

    /**
     * @depends testInitData
     */
    public function testMapKeywordSupportsJoins(): void
    {
        $q = new \Doctrine1\Query();
        $country = $q->from('Record_Country c LEFT JOIN c.City c2 INDEXBY c2.name')->fetchOne();

        $this->assertNotNull($country);
        $coll = $country->City;

        $this->assertTrue(isset($coll['City 1']));
        $this->assertTrue(isset($coll['City 2']));
        $this->assertTrue(isset($coll['City 3']));
        $this->assertEquals('name', $coll->getKeyColumn());
    }

    public function testMapKeywordThrowsExceptionOnNonExistentColumn(): void
    {
        $this->expectException(\Doctrine1\Query\Exception::class);

        $q       = new \Doctrine1\Query();
        $country = $q->from('Record_Country c LEFT JOIN c.City c2 INDEXBY c2.unknown')->fetchOne();
    }
}
