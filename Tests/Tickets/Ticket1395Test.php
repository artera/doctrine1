<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1395Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T1395_MyModel';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $myModel             = new \T1395_MyModel();
            $myModel->dt_created = '2005-10-01';
            $myModel->id         = 0;
            $myModel->save();
        }

        public function testTicket()
        {
            $myModel = static::$conn->getTable('T1395_MyModel')->find(0);
                $this->assertTrue(isset($myModel->dt_created));
                $this->assertTrue(isset($myModel->days_old)); // This is a calculated field from within the \T1395_Listener::preHydrate
                $this->assertTrue(isset($myModel->dt_created_tx)); // This is a calculated field from within the \T1395_Listener::preHydrate
        }
    }
}

namespace {
    class T1395_MyModel extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'notnull' => true]);
            $this->hasColumn('dt_created', 'date');
        }

        public function setUp(): void
        {
            $this->addListener(new \T1395_Listener());
        }
    }

    class T1395_Listener extends Doctrine_Record_Listener
    {
        public function preHydrate(Doctrine_Event $event)
        {
            $data = $event->data;

            // Calculate days since creation
            $days             = (strtotime('now') - strtotime($data['dt_created'])) / (24 * 60 * 60);
            $data['days_old'] = number_format($days, 2);

            self::addSomeData($data);

            $event->data = $data;
        }

        public static function addSomeData(&$data)
        {
            $data['dt_created_tx'] = date('M d, Y', strtotime($data['dt_created']));
        }
    }
}
