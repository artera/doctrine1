<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket749Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['Parent749', 'Record749', 'RelatedRecord749'];

        public static function prepareData(): void
        {
            $record                       = new \Record749();
            $record['title']              = 'Test Record 1';
            $record['Related']['content'] = 'Test Content 1';
            $record->save();

            $record                       = new \Record749();
            $record['title']              = 'Test Record 2';
            $record['Related']['content'] = 'Test Content 2';
            $record->save();
        }

        public function testSelectDataFromSubclassAsCollection()
        {
            $records = \Doctrine_Query::create()->query('FROM Record749 r ORDER BY r.title', []);

            $this->verifyRecords($records);
        }

        public function testSelectDataFromParentClassAsCollection()
        {
            $records = \Doctrine_Query::create()->query('FROM Parent749 p ORDER BY p.title', []);
            $this->verifyRecords($records);
        }

        /**
         * This method is used by both tests, as the collection of records should
         * be identical for both of them if things are working properly.
         */
        private function verifyRecords($records)
        {
            $expected_values = [
            ['title' => 'Test Record 1', 'content' => 'Test Content 1'],
            ['title' => 'Test Record 2', 'content' => 'Test Content 2'],
            ];

            foreach ($records as $record) {
                $this->assertTrue($record instanceof \Record749);
                $expected = array_shift($expected_values);
                $this->assertEquals($record['title'], $expected['title']);
                $this->assertEquals($record['Related']['content'], $expected['content']);
            }
        }
    }
}

namespace {
    class Parent749 extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('mytable');
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'primary'       => true,
                'autoincrement' => true,
                'notnull'       => true,
                ]
            );

            $this->hasColumn('title', 'string', 255, []);
            $this->hasColumn('type', 'integer', 11, []);

            $this->setSubclasses(['Record749' => ['type' => 1]]);
        }

        public function setUp(): void
        {
        }
    }

    class Record749 extends Parent749
    {
        public function setTableDefinition(): void
        {
            parent::setTableDefinition();
            $this->setTableName('mytable');
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne(
                'RelatedRecord749 as Related',
                ['local' => 'id',
                'foreign'      => 'record_id']
            );
        }
    }

    class RelatedRecord749 extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'primary'       => true,
                'autoincrement' => true,
                'notnull'       => true,
                ]
            );

            $this->hasColumn('content', 'string', 255, []);
            $this->hasColumn('record_id', 'integer', null, ['unique' => true,]);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Record749 as Record',
                ['local' => 'record_id',
                                  'foreign'                => 'id',
                'onDelete'               => 'cascade']
            );
        }
    }
}
