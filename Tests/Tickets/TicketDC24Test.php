<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC24Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['ticket_DC24_master', 'ticket_DC24_servant'];

        public static function prepareData(): void
        {
            $servant      = new \Ticket_DC24_Servant;
            $servant->bar = 6;
            $servant->save();

            $servantId = $servant->identifier();

            $master             = new \Ticket_DC24_Master;
            $master->foo        = 6;
            $master->servant_id = $servantId['id'];
            $master->save();
        }

        public function testTest()
        {
            $master = \Doctrine_Query::create()
                ->select('m.*, s.bar AS joe')
                ->from('Ticket_DC24_Master m')
                ->innerJoin('m.Ticket_DC24_Servant s')
                ->where('m.id = 1')
                ->fetchOne();

                $master->foo = 5;
                $master->save();

                $master2 = \Doctrine_Query::create()
                ->select('m.*')
                ->from('Ticket_DC24_Master m')
                ->where('m.id = 1')
                ->fetchOne([], \Doctrine_Core::HYDRATE_ARRAY);
                $this->assertEquals($master2['servant_id'], 1);
        }
    }
}

namespace {
    class Ticket_DC24_Master extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'type'          => 'integer',
                'primary'       => true,
                'autoincrement' => true,
                'length'        => '4',
                ]
            );
            $this->hasColumn(
                'foo',
                'integer',
                4,
                [
                'type'    => 'integer',
                'notnull' => true,
                'length'  => '4',
                ]
            );
            $this->hasColumn(
                'servant_id',
                'integer',
                4,
                [
                'type' => 'integer',
                'length' => '4',
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_DC24_Servant',
                [
                'local'   => 'servant_id',
                'foreign' => 'id']
            );
        }
    }

    class Ticket_DC24_Servant extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'type'          => 'integer',
                'primary'       => true,
                'autoincrement' => true,
                'length'        => '4',
                ]
            );
            $this->hasColumn(
                'bar',
                'integer',
                4,
                [
                'type'    => 'integer',
                'notnull' => true,
                'length'  => '4',
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_DC24_Master as Masters',
                [
                'local'   => 'id',
                'foreign' => 'servant_id']
            );
        }
    }
}
