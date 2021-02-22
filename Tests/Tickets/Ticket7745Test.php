<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket7745Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'RecordTest1';
            static::$tables[] = 'RecordTest2';
            parent::prepareTables();
        }

        public function testDqlCallbacks()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true);

            $table = \Doctrine_Core::getTable('RecordTest2');
            $table->addRecordListener(new \RecordTest2Listener());

            $test2       = new \RecordTest2();
            $test2->name = 'test';

            $test1              = new \RecordTest1();
            $test1->name        = 'test';
            $test1->RecordTest2 = $test2;
            $test1->save();

            $id = $test2->id;
            $test2->free();

            $test2 = \Doctrine_Core::getTable('RecordTest2')
                ->createQuery('a')
                ->select('a.id')
                ->where('a.id = ?', $id)
                ->fetchOne();

            $test2->load();

            $this->assertTrue($test2->RecordTest1 instanceof \Doctrine_Collection);

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_USE_DQL_CALLBACKS, false);
        }
    }
}

namespace {
    class RecordTest1 extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string');
            $this->hasColumn('record_test2_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne(
                'RecordTest2',
                [
                'local'   => 'record_test2_id',
                'foreign' => 'id'
                ]
            );
        }
    }

    class RecordTest2 extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string');
        }

        public function setUp(): void
        {
            $this->hasMany(
                'RecordTest1',
                [
                'local'   => 'id',
                'foreign' => 'record_test2_id'
                ]
            );
        }
    }

    class RecordTest2Listener extends Doctrine_Record_Listener
    {
        public function preDqlSelect(Doctrine_Event $event)
        {
            $params = $event->getParams();
            $alias  = $params['alias'];

            $event->getQuery()->leftJoin($alias . '.RecordTest1');
        }
    }
}
