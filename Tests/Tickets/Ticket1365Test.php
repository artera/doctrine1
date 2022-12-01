<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1365Test extends DoctrineUnitTestCase
    {
        public function testInit()
        {
            static::$dbh  = new \Doctrine1\Adapter\Mock('mysql');
            static::$conn = \Doctrine1\Manager::getInstance()->openConnection(static::$dbh);

            static::$conn->setCharset('utf8');
            static::$conn->setUseNativeEnum(true);
        }

        public static function prepareData(): void
        {
        }

        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T1365_Person';
            static::$tables[] = 'T1365_Skill';
            static::$tables[] = 'T1365_PersonHasSkill';

            parent :: prepareTables();
        }

        public function testTicket()
        {
            $q = \Doctrine1\Query::create()
            ->select('s.*, phs.*')
            ->from('T1365_Skill s')
            ->leftJoin('s.T1365_PersonHasSkill phs')
            ->where('phs.value0 > phs.value1');

            $this->assertEquals(
                $q->getSqlQuery(),
                'SELECT l.id AS l__id, l.name AS l__name, ' .
                'l2.id AS l2__id, l2.fk_person_id AS l2__fk_person_id, l2.fk_skill_id AS l2__fk_skill_id, l2.value0 AS l2__value0, l2.value1 AS l2__value1 ' .
                'FROM la__skill l LEFT JOIN la__person_has_skill l2 ON l.id = l2.fk_skill_id ' .
                'WHERE (l2.value0 > l2.value1)'
            );
        }
    }
}

namespace {
    class T1365_Person extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('la__person');

            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany('T1365_PersonHasSkill', ['local' => 'id', 'foreign' => 'fk_person_id']);
        }
    }


    class T1365_Skill extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('la__skill');

            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany('T1365_PersonHasSkill', ['local' => 'id', 'foreign' => 'fk_skill_id']);
        }
    }


    class T1365_PersonHasSkill extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('la__person_has_skill');

            $this->hasColumn(
                'fk_person_id',
                'integer',
                8,
                [
                'type' => 'integer', 'length' => '8'
                ]
            );

            $this->hasColumn(
                'fk_skill_id',
                'integer',
                8,
                [
                'type' => 'integer', 'length' => '8'
                ]
            );

            $this->hasColumn(
                'value0',
                'enum',
                3,
                [
                'type' => 'enum', 'values' => [
                0 => '0', 1 => '1', 2 => '2', 3 => '3'
                ], 'default' => 0, 'notnull' => true, 'length' => '3'
                ]
            );

            $this->hasColumn(
                'value1',
                'enum',
                3,
                [
                'type' => 'enum', 'values' => [
                0 => '0', 1 => '1', 2 => '2', 3 => '3'
                ], 'default' => 0, 'notnull' => true, 'length' => '3'
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasOne('T1365_Person', ['local' => 'fk_person_id', 'foreign' => 'id']);
            $this->hasOne('T1365_Skill', ['local' => 'fk_skill_id', 'foreign' => 'id']);
        }
    }
}
