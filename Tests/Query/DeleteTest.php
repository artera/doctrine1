<?php
namespace Tests\Query {
    use Tests\DoctrineUnitTestCase;

    class DeleteTest extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'DeleteTestModel';
            parent::prepareTables();
        }

        public function testDeleteAllWithColumnAggregationInheritance()
        {
            $q = new \Doctrine_Query();

            $q->parseDqlQuery('DELETE FROM User');

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity WHERE (type = 0)');

            $q = new \Doctrine_Query();

            $q->delete()->from('User');

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity WHERE (type = 0)');
        }

        public function testDeleteAll()
        {
            $q = new \Doctrine_Query();

            $q->parseDqlQuery('DELETE FROM Entity');

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity');

            $q = new \Doctrine_Query();

            $q->delete()->from('Entity');

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity');
        }

        public function testDeleteWithCondition()
        {
            $q = new \Doctrine_Query();

            $q->parseDqlQuery('DELETE FROM Entity WHERE id = 3');

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity WHERE (id = 3)');

            $q = new \Doctrine_Query();

            $q->delete()->from('Entity')->where('id = 3');

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity WHERE (id = 3)');
        }

        public function testDeleteWithLimit()
        {
            $q = new \Doctrine_Query();

            $q->parseDqlQuery('DELETE FROM Entity LIMIT 20');

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity LIMIT 20');

            $q = new \Doctrine_Query();

            $q->delete()->from('Entity')->limit(20);

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity LIMIT 20');
        }

        public function testDeleteWithLimitAndOffset()
        {
            $q = new \Doctrine_Query();

            $q->parseDqlQuery('DELETE FROM Entity LIMIT 10 OFFSET 20');

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity LIMIT 10 OFFSET 20');

            $q = new \Doctrine_Query();

            $q->delete()->from('Entity')->limit(10)->offset(20);

            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM entity LIMIT 10 OFFSET 20');
        }

        public function testDeleteWithFromInDeleteFunction()
        {
            $q = \Doctrine_Core::getTable('Entity')->createQuery()->delete();
            $this->assertEquals($q->getDql(), 'DELETE FROM Entity');
            $q = \Doctrine_Query::create()->delete('Entity');
            $this->assertEquals($q->getDql(), 'DELETE FROM Entity');

            $q = \Doctrine_Core::getTable('DeleteTestModel')->createQuery()->delete('DeleteTestModel');
            $this->assertEquals($q->getDql(), 'DELETE FROM DeleteTestModel');
            $this->assertEquals($q->getSqlQuery(), 'DELETE FROM delete_test_model');
            $q->execute();
        }
    }
}

namespace {
    class DeleteTestModel extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }
    }
}
