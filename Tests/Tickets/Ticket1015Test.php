<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1015Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T1015_Person';
            static::$tables[] = 'T1015_Points';

            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $person                          = new \T1015_Person();
            $person['name']                  = 'James';
            $person['T1015_Points']['total'] = 15;
            $person->save();
        }


        public function testDoctrineQueryJoinSelect()
        {
            $q = new \Doctrine1\Query();
            $q->select('person.id, points.total')
            ->from('T1015_Person person')
            ->innerJoin('person.T1015_Points points WITH person.id = 1');

            $results = $q->execute([], \Doctrine1\HydrationMode::Array);
            //var_dump($results);
            $person = $results[0];

            // number of points for person id of 1 should be 15
            $this->assertEquals(15, $person['T1015_Points']['total']); //THIS WILL FAIL
        }

        public function testDoctrineRawSQLJoinSelect()
        {
            $q = new \Doctrine1\RawSql();
            $q->select('{person.id}, {points.total}')
            ->from('person person INNER JOIN points points ON person.id = points.person_id AND person.id=1')
            ->addComponent('person', 'T1015_Person person')
            ->addComponent('points', 'person.T1015_Points points');

            $results = $q->execute([], \Doctrine1\HydrationMode::Array);
            //var_dump($results);
            $person = $results[0];

            // number of points for person id of 1 should be 15
            $this->assertEquals(15, $person['T1015_Points']['total']);
        }
    }
}

namespace {
    class T1015_Person extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('person');
            $this->hasColumn('id', 'integer', 15, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('name', 'string', 50);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne('T1015_Points', ['local' => 'id', 'foreign' => 'person_id']);
        }
    }

    class T1015_Points extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('points');
            $this->hasColumn('person_id', 'integer', 15, ['primary' => true, 'notnull' => true]);
            $this->hasColumn('total', 'integer', 3);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne('T1015_Person', ['local' => 'person_id', 'foreign' => 'id']);
        }
    }
}
