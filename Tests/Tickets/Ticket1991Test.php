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
            $q = new \Doctrine1\Query();
            $q->select('t.name')->from('NewTag t INDEXBY t.name');
            $results = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY);
        }
    }
}

namespace {
    class NewTag extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('tag');
            $this->hasColumn('name', 'string', 100, ['type' => 'string', 'length' => '100']);
        }
    }
}
