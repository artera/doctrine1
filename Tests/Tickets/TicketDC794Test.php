<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC794Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC794_Model';
            parent::prepareTables();
        }

        public function testTest()
        {
            $table = \Doctrine::getTable('Ticket_DC794_Model');

            $this->assertEquals($table->buildFindByWhere('IdOrigenOportunidadClienteOrId'), '(dctrn_find.idOrigenOportunidadCliente = ? OR dctrn_find.id = ?)');
            $this->assertEquals($table->buildFindByWhere('IdAndIdOrIdOrigenOportunidadCliente'), 'dctrn_find.id = ? AND (dctrn_find.id = ? OR dctrn_find.idOrigenOportunidadCliente = ?)');
            $this->assertEquals($table->buildFindByWhere('UsernameOrIdOrIdOrigenOportunidadCliente'), '(dctrn_find.Username = ? OR dctrn_find.id = ? OR dctrn_find.idOrigenOportunidadCliente = ?)');
        }
    }
}

namespace {
    class Ticket_DC794_Model extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'id',
                'integer',
                null,
                [
                'type'          => 'integer',
                'unsigned'      => false,
                'primary'       => true,
                'autoincrement' => true,
                ]
            );
            $this->hasColumn('idOrigenOportunidadCliente', 'string', 255);
            $this->hasColumn('Username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
        }
    }
}
