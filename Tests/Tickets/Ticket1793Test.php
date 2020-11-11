<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1793Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $order1         = new \Ticket_1793_Order;
            $order1->status = 'new';
            $order1->save();

            /* The enum column can be changed if the value isn't equal to that of one of the column aggregation's keyValue's: */
            $order2         = new \Ticket_1793_Order;
            $order2->status = 'shipped'; // 'shipped' isn't one of the column aggregation keyValue's
            $order2->save();

            // Same as $order2
            $order3         = new \Ticket_1793_Order;
            $order3->status = 'shipped';
            $order3->save();

            $order4         = new \Ticket_1793_Order;
            $order4->status = 'new';
            $order4->save();
        }

        protected static array $tables = ['Ticket_1793_Order', 'Ticket_1793_OrdersNew', 'Ticket_1793_OrdersCompleted'];

        public function testTicket()
        {
            // Doesn't work:
            $order1 = \Doctrine_Core::getTable('Ticket_1793_Order')->find(1);
            //echo $order1->status; // 'new'
            $order1->status = 'completed';
            $order1->save();
            $this->assertEquals($order1->status, 'completed');

            // Works because previous status was not one of the column aggregation's keyValue's
            $order2 = \Doctrine_Core::getTable('Ticket_1793_Order')->find(2);
            //echo $order2->status; // 'shipping'
            $order2->status = 'new';
            $order2->save();
            $this->assertEquals($order2->status, 'new');

            // This works because it reuses $order2 from above:
            //echo $order2->status; // 'new'
            $order2->status = 'completed';
            $order2->save();
            $this->assertEquals($order2->status, 'completed');

            // Works because previous status was not one of the column aggregation's keyValue's
            $order3 = \Doctrine_Core::getTable('Ticket_1793_Order')->find(3);
            //echo $order2->status; // 'shipping'
            $order3->status = 'new';
            $order3->save();
            $this->assertEquals($order3->status, 'new');

            // Now this doesn't work because it's re-finding order #3 instead of re-using $order3.
            $order3 = \Doctrine_Core::getTable('Ticket_1793_Order')->find(3);
            //echo $order3->status; // 'new'
            $order3->status = 'completed';
            $order3->save();
            $this->assertEquals($order3->status, 'completed');

            /* Changing the table name to Ticket_1793_OrdersNew still fails. */
            $order4 = \Doctrine_Core::getTable('Ticket_1793_OrdersNew')->find(4);
            //echo $order4->status; // 'new'
            $order4->status = 'completed';
            $order4->save();
            $this->assertEquals($order4->status, 'completed');

            // This works.
            $o1 = \Doctrine_Query::create()
            ->update('Ticket_1793_Order o')
            ->set('o.status', '?', 'completed')
            ->where('o.id = ?', 1)
            ->execute();
            $order1 = \Doctrine_Core::getTable('Ticket_1793_Order')->find(1);
            $this->assertEquals($order1->status, 'completed');
        }
    }
}

namespace {
    class Ticket_1793_Order extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('ticket_1793_orders');
            $this->hasColumn('id', 'integer', 4, ['type' => 'integer', 'unsigned' => '1', 'primary' => true, 'autoincrement' => true, 'length' => '4']);
            $this->hasColumn('status', 'enum', null, ['type' => 'enum', 'values' => [0 => 'new', 1 => 'completed', 2 => 'shipped']]);

            $this->setSubClasses(['Ticket_1793_OrdersNew' => ['status' => 'new'], 'Ticket_1793_OrdersCompleted' => ['status' => 'completed']]);
        }
    }

    class Ticket_1793_OrdersCompleted extends Ticket_1793_Order
    {
    }

    class Ticket_1793_OrdersNew extends Ticket_1793_Order
    {
    }
}
