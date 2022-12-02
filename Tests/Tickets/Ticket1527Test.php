<?php

namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1527Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $yml = <<<END
---
Ticket_1527_User:
  columns:
    username:
      type: string(255)
      extra:
        test: 123
    password:
      type: string(255)
END;

        $import = new \Doctrine1\Import\Schema();
        $schema = $import->buildSchema([$yml], 'yml');
        $this->assertEquals($schema['Ticket_1527_User']['columns']['username']['extra']['test'], '123');

        $path = dirname(__FILE__) . '/../tmp';
        $import->importSchema([$yml], 'yml', $path);

        include_once $path . '/BaseTicket_1527_User.php';
        include_once $path . '/Ticket_1527_User.php';
        $username = \Doctrine1\Core::getTable('Ticket_1527_User')->getDefinitionOf('username');
        $this->assertEquals($username['extra']['test'], '123');
    }
}
