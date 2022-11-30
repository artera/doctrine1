<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket626DTest extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        protected static array $tables = ['T626D_Student1'];

        protected function newStudent($cls, $id, $name)
        {
            $u       = new $cls;
            $u->id   = $id;
            $u->name = $name;
            $u->save();
            return $u;
        }

        public function testFieldNames()
        {
            $student1 = $this->newStudent('T626D_Student1', '07090002', 'First Student');

            $student = \Doctrine1\Core::getTable('T626D_Student1')->find('07090002');
        }
    }
}

namespace {
    class T626D_Student1 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('T626D_Student_record_1');

            $this->hasColumn('s_id as id', 'varchar', 30, [  'primary' => true,]);
            $this->hasColumn('s_name as name', 'varchar', 50, []);
        }
    }
}
