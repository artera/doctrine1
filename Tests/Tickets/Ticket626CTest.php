<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket626CTest extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        protected static array $tables = ['T626C_Student1', 'T626C_Student2'];

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
            $student1 = $this->newStudent('T626C_Student1', '07090002', 'First Student');

            $students = \Doctrine1\Query::create()
                ->from('T626C_Student1 s INDEXBY s.id')
                ->execute([], \Doctrine1\Core::HYDRATE_ARRAY);
        }

        public function testColNames()
        {
            $student1 = $this->newStudent('T626C_Student2', '07090002', 'First Student');

            $students = \Doctrine1\Query::create()
                ->from('T626C_Student2 s INDEXBY s.id')
                ->execute([], \Doctrine1\Core::HYDRATE_ARRAY);
        }
    }
}

namespace {
    class T626C_Student1 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('T626C_Student_record_1');

            $this->hasColumn('s_id as id', 'varchar', 30, [  'primary' => true,]);
            $this->hasColumn('s_name as name', 'varchar', 50, []);
        }
    }

    class T626C_Student2 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('T626C_Student_record_2');

            $this->hasColumn('id', 'varchar', 30, [  'primary' => true,]);
            $this->hasColumn('name', 'varchar', 50, []);
        }
    }
}
