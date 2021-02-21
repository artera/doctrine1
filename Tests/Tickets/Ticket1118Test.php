<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1118Test extends DoctrineUnitTestCase
{
    // Test that when a foreign key is detected that it sets the foreign key to the same type and length
    // of the related table primary key
    public function testTest()
    {
        $yml = <<<END
---
detect_relations: true
Ticket_1118_User:
  columns:
    username: string(255)
    password: string(255)
    ticket__1118__profile_id: string

Ticket_1118_Profile:
  columns:
    id:
      type: integer(4)
      autoincrement: true
      primary: true
    name: string(255)
END;
        file_put_contents('test.yml', $yml);

        $import = new \Doctrine_Import_Schema();
        $array  = $import->buildSchema(['test.yml'], 'yml');
        // Test that ticket__1118__profile_id is changed to to be integer(4) since the primary key of
        // the relationship is set to that
        $this->assertEquals($array['Ticket_1118_User']['columns']['ticket__1118__profile_id']['type'], 'integer');
        $this->assertEquals($array['Ticket_1118_User']['columns']['ticket__1118__profile_id']['length'], '4');

        unlink('test.yml');
    }
}
