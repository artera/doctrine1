<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1192Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_1192_CPK';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $test1          = new \Ticket_1192_CPK();
            $test1->user_id = 1;
            $test1->name    = 'Test 1';
            $test1->save();

            $test2          = new \Ticket_1192_CPK();
            $test2->user_id = 2;
            $test2->name    = 'Test 2';
            $test2->save();

            $test3          = new \Ticket_1192_CPK();
            $test3->user_id = 2;
            $test3->name    = 'Test 3';
            $test3->save();
        }

        public function testTest()
        {
            $q = \Doctrine_Query::create()
            ->from('Ticket_1192_CPK t')
            ->groupBy('t.user_id');

            $this->assertEquals($q->getCountSqlQuery(), 'SELECT COUNT(*) AS num_results FROM ticket_1192__c_p_k t GROUP BY t.user_id');
            $count = $q->count();
            $this->assertEquals($count, 2);
            $this->assertEquals($count, $q->execute()->count());
        }
    }
}

namespace {
    class Ticket_1192_CPK extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('user_id', 'integer', 4, ['primary' => true]);
            $this->hasColumn('name', 'string', 255);
        }
    }
}
