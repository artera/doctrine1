<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket923Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $d                 = new \T923_Diagnostic();
            $d->id_type        = 101;
            $d->id             = 26;
            $d->diagnostic_id  = 75444;
            $d->diag_timestamp = '2008-03-27 12:00:00';
            $d->operator_id    = 1001;
            $d->save();

            $d                 = new \T923_Diagnostic();
            $d->id_type        = 101;
            $d->id             = 27;
            $d->diagnostic_id  = 75445;
            $d->diag_timestamp = '2008-03-27 13:00:00';
            $d->operator_id    = 1001;
            $d->save();

            $d                 = new \T923_Diagnostic();
            $d->id_type        = 101;
            $d->id             = 28;
            $d->diagnostic_id  = 75445;
            $d->diag_timestamp = '2008-03-27 14:00:00';
            $d->operator_id    = 1001;
            $d->save();
        }

        public static function prepareTables(): void
        {
            static::$tables[] = 'T923_Diagnostic';
            parent::prepareTables();
        }

        public function testTicket()
        {
            $q      = new \Doctrine_Query();
                $result = $q->select('d.*')
                ->from('T923_Diagnostic d')
                ->where('d.diag_timestamp >= ? AND d.diag_timestamp <= ?', ['2008-03-27 00:00:00', '2008-03-27 23:00:00'])
                ->addWhere('d.id_type = ?', ['101'])
                ->orderBy('d.diag_timestamp')
                ->limit(20)
                ->offset(0)
                ->execute();

                $this->assertEquals($result->count(), 3);
        }
    }
}

namespace {
    class T923_Diagnostic extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('diagnostics');
            $this->hasColumn('id_type', 'integer', 4);
            $this->hasColumn('id', 'integer', 4);
            $this->hasColumn('diagnostic_id', 'integer', 4);
            $this->hasColumn('operator_id', 'integer', 4);
            $this->hasColumn('diag_timestamp', 'timestamp', null);
        }

        public function setUp()
        {
        }
    }
}
