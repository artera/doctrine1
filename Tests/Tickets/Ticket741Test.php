<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket741Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        protected static array $tables = ['Parent741', 'Child741'];

        public function testTicket()
        {
            $moo         = new \Parent741();
            $moo->amount = 1000;
            $cow         = new \Child741();

            $moo->Cows[] = $cow;
            $cow->Moo    = $moo;
            $moo->save();
            $this->assertEquals($moo->amount, 0);
        }
    }
}

namespace {
    use Doctrine1\Event;

    class Parent741 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'primary'       => true,
                'autoincrement' => true,
                'notnull'       => true,
                ]
            );

            $this->hasColumn('amount', 'integer');
        }

        public function setUp(): void
        {
            $this->hasMany('Child741 as Cows', ['local' => 'id', 'foreign' => 'moo_id']);
        }
    }

    class Child741 extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'primary'       => true,
                'autoincrement' => true,
                'notnull'       => true,
                ]
            );

            $this->hasColumn('moo_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne('Parent741 as Moo', ['local' => 'moo_id', 'foreign' => 'id']);
        }

        public function postInsert(Event $e): void
        {
            $this->Moo->state(\Doctrine1\Record\State::DIRTY);
            //echo "State: ". $this->Moo->state() . " \t Amount: " . $this->Moo->amount . "\n";
            $this->Moo->amount = 0;
            //echo "State: ". $this->Moo->state() . " \t Amount: " . $this->Moo->amount . "\n";
            $this->Moo->save();
            //echo "State: ". $this->Moo->state() . " \t Amount: " . $this->Moo->amount . "\n";
            $this->Moo->refresh();
            //echo "State: ". $this->Moo->state() . " \t Amount: " . $this->Moo->amount . "\n";
            /*
            This outputs the following
            State: 6         Amount: 1000
            State: 6         Amount: 0
            State: 6         Amount: 0
            State: 3         Amount: 1000

            */
        }
    }
}
