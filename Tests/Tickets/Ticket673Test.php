<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket673Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        protected static array $tables = ['T673_Student'];

        public function testTicket()
        {
            $q = \Doctrine1\Query::create()
            ->update('T673_Student s')
            ->set('s.foo', 's.foo + 1')
            ->where('s.id = 2');

            $this->assertTrue(preg_match_all('/(s_foo)/', $q->getSqlQuery(), $m) === 2);
            $this->assertTrue(preg_match_all('/(s_id)/', $q->getSqlQuery(), $m) === 1);

            $q->execute();

            $q = \Doctrine1\Query::create()
            ->delete()
            ->from('T673_Student s')
            ->where('s.name = ? AND s.foo < ?', 'foo', 3);

            $this->assertTrue(preg_match_all('/(s_name)/', $q->getSqlQuery(), $m) === 1);
            $this->assertTrue(preg_match_all('/(s_foo)/', $q->getSqlQuery(), $m) === 1);

            $q->execute();
        }
    }
}

namespace {
    class T673_Student extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('T673_Student_record');

            $this->hasColumn('s_id as id', 'varchar', 30, [  'primary' => true,]);
            $this->hasColumn('s_foo as foo', 'integer', 4, ['notnull' => true]);
            $this->hasColumn('s_name as name', 'varchar', 50, []);
        }
    }
}
