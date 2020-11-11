<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1991Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'NewTag';

            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $tag       = new \NewTag();
            $tag->name = 'name';
            $tag->save();

            $tag       = new \NewTag();
            $tag->name = 'foobar';
            $tag->save();
        }


        public function testHydratation()
        {
            $q = new \Doctrine_Query();
            $q->select('t.name')->from('NewTag t INDEXBY t.name');
            $results = $q->execute([], \Doctrine_Core::HYDRATE_ARRAY);
        }
    }
}

namespace {
    class NewTag extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('tag');
            $this->hasColumn('name', 'string', 100, ['type' => 'string', 'length' => '100']);
        }
    }
}
