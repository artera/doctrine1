<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1986Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        protected static array $tables = ['Testing_Ticket_1986_1','Testing_Ticket_1986_2','Testing_Ticket_1986Link'];

        public function testTicket()
        {
            // this works
            $t1 = new \Testing_Ticket_1986_1();
            $t1->get('others');
            $t1->save();
            $t1->get('others');
            
// this not: relation is not accessed before save and is gone afterwards
            $t2 = new \Testing_Ticket_1986_1();
            $t2->save();
            $t2->get('others');
        }
    }
}

namespace {
    class Testing_Ticket_1986_1 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('testing_ticket_1986_1');
            $this->hasColumn('name', 'string', 64, []);
        }
        public function setUp()
        {
            $this->hasMany('Testing_Ticket_1986_2 as others', ['refClass' => 'Testing_Ticket_1986Link', 'local' => 'id_1', 'foreign' => 'id_2']);
        }
    }

    class Testing_Ticket_1986_2 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('testing_ticket_1986_2');
            $this->hasColumn('value', 'string', 64, []);
        }

        public function setUp()
        {
            $this->hasMany('Testing_Ticket_1986_1', ['refClass' => 'Testing_Ticket_1986Link', 'local' => 'id_2', 'foreign' => 'id_1']);
        }
    }

    class Testing_Ticket_1986Link extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('testing_ticket_1986_link');
            $this->hasColumn('id_1', 'integer', null, []);
            $this->hasColumn('id_2', 'integer', null, []);
        }

        public function setUp()
        {
            // setup relations
            $this->hasOne('Testing_Ticket_1986_1 as rel1', ['local' => 'id_1', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Testing_Ticket_1986_2 as rel2', ['local' => 'id_2', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }
}
