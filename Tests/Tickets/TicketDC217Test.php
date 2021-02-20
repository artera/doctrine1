<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC217Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC217_Industry';
            parent::prepareTables();
        }

        public function testTest()
        {
            $o       = new \Ticket_DC217_Industry();
            $o->name = 'test';
            //$o->parent_id = null;
            $o->save();
        }
    }
}

namespace {
    class Ticket_DC217_Industry extends Doctrine_Record
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
                'parent_id',
                'integer',
                4,
                [
                'type'    => 'integer',
                'notnull' => false,
                'length'  => '4',
                ]
            );

            $this->hasColumn(
                'name',
                'string',
                30,
                [
                'type'    => 'string',
                'notnull' => true,
                'length'  => '30',
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_DC217_Industry as ParentIndustry',
                [
                'local'   => 'parent_id',
                'foreign' => 'id']
            );

            $this->hasMany(
                'Ticket_DC217_Industry as ChildIndustries',
                [
                'local'   => 'id',
                'foreign' => 'parent_id']
            );
        }
    }
}
