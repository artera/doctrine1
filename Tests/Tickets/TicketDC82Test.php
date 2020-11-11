<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC82Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            static::$dbh  = new \Doctrine_Adapter_Mock('pgsql');
            static::$conn = static::$manager->openConnection(static::$dbh);

            $sql = static::$conn->export->exportClassesSql(['Ticket_DC82_Article']);
            $this->assertEquals(
                $sql,
                [
                'CREATE UNIQUE INDEX model_unique_title ON ticket__d_c82__article (title) WHERE deleted = false',
                "CREATE TABLE ticket__d_c82__article (id BIGSERIAL, title VARCHAR(128) NOT NULL UNIQUE, deleted BOOLEAN DEFAULT 'false' NOT NULL, PRIMARY KEY(id))"
                ]
            );
        }
    }
}

namespace {
    class Ticket_DC82_Article extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('title', 'string', 128, ['notnull', 'unique' => ['where' => 'deleted = false']]);
            $this->hasColumn('deleted', 'boolean', 1, ['notnull', 'default' => false]);
            $this->index('model_unique_title', ['fields' => ['title'], 'where' => 'deleted = false', 'type' => 'unique' ]);
        }
    }
}
