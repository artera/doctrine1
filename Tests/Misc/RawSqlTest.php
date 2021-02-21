<?php
namespace Tests\Misc;

use Tests\DoctrineUnitTestCase;

class RawSqlTest extends DoctrineUnitTestCase
{
    public function testQueryParser()
    {
        $sql   = 'SELECT {p.*} FROM photos p';
        $query = new \Doctrine_RawSql(static::$connection);
        $query->parseDqlQuery($sql);

        $this->assertEquals($query->getSqlQueryPart('from'), ['photos p']);


        $sql = 'SELECT {p.*} FROM (SELECT p.* FROM photos p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE t.tag_id = 65) p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE p.can_see = -1 AND t.tag_id = 62 LIMIT 200';
        $query->parseDqlQuery($sql);

        $this->assertEquals($query->getSqlQueryPart('from'), ['(SELECT p.* FROM photos p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE t.tag_id = 65) p LEFT JOIN photos_tags t ON t.photo_id = p.id']);
        $this->assertEquals($query->getSqlQueryPart('where'), ['p.can_see = -1 AND t.tag_id = 62']);
        $this->assertEquals($query->getSqlQueryPart('limit'), [200]);
    }

    public function testAsteriskOperator()
    {
        // Selecting with *

        $query = new \Doctrine_RawSql(static::$connection);
        $query->parseDqlQuery('SELECT {entity.*} FROM entity');
        $fields = $query->getFields();

        $this->assertEquals($fields, ['entity.*']);

        $query->addComponent('entity', 'Entity');

        $coll = $query->execute();

        $this->assertEquals($coll->count(), 11);
    }

    public function testLazyPropertyLoading()
    {
        $query = new \Doctrine_RawSql(static::$connection);
        static::$connection->clear();

        // selecting proxy objects (lazy property loading)

        $query->parseDqlQuery('SELECT {entity.name}, {entity.id} FROM entity');
        $fields = $query->getFields();

        $this->assertEquals($fields, ['entity.name', 'entity.id']);
        $query->addComponent('entity', 'Entity');

        $coll = $query->execute();

        $this->assertEquals($coll->count(), 11);

        $this->assertEquals($coll[0]->state(), \Doctrine_Record_State::PROXY());
        $this->assertEquals($coll[3]->state(), \Doctrine_Record_State::PROXY());
    }
    public function testSmartMapping()
    {
        $query = new \Doctrine_RawSql(static::$connection);
        // smart component mapping (no need for additional addComponent call

        $query->parseDqlQuery('SELECT {entity.name}, {entity.id} FROM entity');
        $fields = $query->getFields();

        $this->assertEquals($fields, ['entity.name', 'entity.id']);

        $coll = $query->execute();

        $this->assertEquals($coll->count(), 11);

        $this->assertEquals($coll[0]->state(), \Doctrine_Record_State::PROXY());
        $this->assertEquals($coll[3]->state(), \Doctrine_Record_State::PROXY());
    }

    public function testMultipleComponents()
    {
        $query = new \Doctrine_RawSql(static::$connection);
        // multi component fetching

        $query->parseDqlQuery('SELECT {entity.name}, {entity.id}, {phonenumber.*} FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id');

        $query->addComponent('entity', 'Entity');

        $query->addComponent('phonenumber', 'Entity.Phonenumber');

        $coll = $query->execute();
        $this->assertEquals($coll->count(), 11);

        $count = static::$conn->count();

        $coll[4]->Phonenumber[0]->phonenumber;
        $this->assertEquals($count, static::$conn->count());

        $coll[5]->Phonenumber[0]->phonenumber;
        $this->assertEquals($count, static::$conn->count());
    }

    public function testAliasesAreSupportedInAddComponent()
    {
        $query = new \Doctrine_RawSql();
        $query->parseDqlQuery('SELECT {entity.name}, {entity.id}, {phonenumber.*} FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id');

        $query->addComponent('entity', 'Entity e');
        $query->addComponent('phonenumber', 'e.Phonenumber');

        $this->assertEquals(array_keys($query->getQueryComponents()), ['e', 'e.Phonenumber']);

        $coll = $query->execute();
        $this->assertEquals($coll->count(), 11);

        $count = static::$conn->count();

        $coll[4]['Phonenumber'][0]['phonenumber'];
        $this->assertEquals($count, static::$conn->count());

        $coll[5]['Phonenumber'][0]['phonenumber'];
        $this->assertEquals($count, static::$conn->count());
    }
    public function testPrimaryKeySelectForcing()
    {
        // forcing the select of primary key fields

        $query = new \Doctrine_RawSql(static::$connection);

        $query->parseDqlQuery('SELECT {entity.name} FROM entity');

        $coll = $query->execute();

        $this->assertEquals($coll->count(), 11);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }

    public function testConvenienceMethods()
    {
        $query = new \Doctrine_RawSql(static::$connection);
        $query->select('{entity.name}')->from('entity');
        $query->addComponent('entity', 'User');

        $coll = $query->execute();

        $this->assertEquals($coll->count(), 8);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }

    public function testColumnAggregationInheritance()
    {
        // forcing the select of primary key fields

        $query = new \Doctrine_RawSql(static::$connection);

        $query->parseDqlQuery('SELECT {entity.name} FROM entity');
        $query->addComponent('entity', 'User');
        $coll = $query->execute();

        $this->assertEquals($coll->count(), 8);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }

    public function testColumnAggregationInheritanceWithOrderBy()
    {
        // forcing the select of primary key fields

        $query = new \Doctrine_RawSql(static::$connection);

        $query->parseDqlQuery('SELECT {entity.name} FROM entity ORDER BY entity.name');
        $query->addComponent('entity', 'User');

        $this->assertEquals($query->getSqlQuery(), 'SELECT entity.name AS entity__name, entity.id AS entity__id FROM entity WHERE entity.type = 0 ORDER BY entity.name');


        $coll = $query->execute();

        $this->assertEquals($coll->count(), 8);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }

    public function testQueryParser2()
    {
        $query = new \Doctrine_RawSql();

        $query->parseDqlQuery("SELECT {entity.name} FROM (SELECT entity.name FROM entity WHERE entity.name = 'something') WHERE entity.id = 2 ORDER BY entity.name");

        $this->assertEquals(
            $query->getSqlQuery(),
            "SELECT entity.name AS entity__name, entity.id AS entity__id FROM (SELECT entity.name FROM entity WHERE entity.name = 'something') WHERE entity.id = 2 ORDER BY entity.name"
        );
    }

    public function testSelectingWithoutIdentifiersOnRootComponent()
    {
        $query = new \Doctrine_RawSql();

        $query->parseDqlQuery('SELECT {entity.name}, {phonenumber.*} FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id LIMIT 3');
        $query->addComponent('entity', 'Entity');
        $query->addComponent('phonenumber', 'Entity.Phonenumber');
        $this->assertEquals($query->getSqlQuery(), 'SELECT entity.name AS entity__name, entity.id AS entity__id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id LIMIT 3');
        $coll = $query->execute([], \Doctrine_Core::HYDRATE_ARRAY);

        $this->assertEquals(count($coll), 3);
    }

    public function testSwitchingTheFieldOrder()
    {
        $query = new \Doctrine_RawSql();

        $query->parseDqlQuery('SELECT {phonenumber.*}, {entity.name} FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id LIMIT 3');
        $query->addComponent('entity', 'Entity');
        $query->addComponent('phonenumber', 'Entity.Phonenumber');
        $this->assertEquals($query->getSqlQuery(), 'SELECT entity.name AS entity__name, entity.id AS entity__id, phonenumber.id AS phonenumber__id, phonenumber.phonenumber AS phonenumber__phonenumber, phonenumber.entity_id AS phonenumber__entity_id FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id LIMIT 3');
        $coll = $query->execute([], \Doctrine_Core::HYDRATE_ARRAY);

        $this->assertEquals(count($coll), 3);
    }

    public function testParseQueryPartShouldAddPartIfNotSelectAndAppend()
    {
        $query = new \Doctrine_Rawsql();
        $query->parseDqlQueryPart('test', 'test', true);
        $parts = $query->getSqlParts();
        $this->assertTrue(isset($parts['test']));
        $this->assertTrue(is_array($parts['test']));
        $this->assertTrue(isset($parts['test'][0]));
        $this->assertEquals('test', $parts['test'][0]);
    }

    public function testParseQueryShouldExtractGroupBy()
    {
        $query = new \Doctrine_RawSql();
        $query->parseDqlQuery('having group');
        $parts = $query->getSqlParts();
        $this->assertEquals($parts['having'][0], 'group');
    }

    public function testThrowExceptionIfFieldNameIsOnWrongForm()
    {
        $query = new \Doctrine_RawSql();
        $query->parseDqlQueryPart('select', '{test}');
        $this->expectException(\Doctrine_RawSql_Exception::class);
        $query->getSqlQuery();
    }

    public function testThrowExceptionIfAliasDoesNotExist()
    {
        $query = new \Doctrine_RawSql();
        $query->parseDqlQueryPart('select', '{test.test}');
        $this->expectException(\Doctrine_RawSql_Exception::class);
        $query->getSqlQuery();
    }
}
