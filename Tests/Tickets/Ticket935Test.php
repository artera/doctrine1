<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket935Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $d     = new \EnumUpdateBug();
            $d->id = 1;
            $d->save();
        }

        public static function prepareTables(): void
        {
            static::$tables[] = 'EnumUpdateBug';
            parent::prepareTables();
        }

        public function testTicket()
        {
            $q = new \Doctrine_Query();
                $q->update('EnumUpdateBug')
                ->set('bla_id', '?', 5)
                ->set('separator', '?', 'pipe')
                ->where('id = 1')
                ->execute();
            
            $q   = new \Doctrine_Query();
            $row = $q->select('a.*')
            ->from('EnumUpdateBug a')
            ->where('a.id = 1')
            ->fetchOne();

            $this->assertEquals($row->bla_id, 5);
        }
    }
}

namespace {
    class EnumUpdateBug extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('enumupdatebug');
            $this->hasColumn('id', 'integer', 3, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('bla_id', 'integer', 2, ['unsigned' => true]);
            $this->hasColumn('separator', 'enum', 1, ['values' => [  0 => 'comma',   1 => 'pipe', ]]);
        }

        public function setUp()
        {
        }
    }
}
