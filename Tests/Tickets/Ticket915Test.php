<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket915Test extends DoctrineUnitTestCase
{
    protected static array $tables = ['Account'];

    public static function prepareData(): void
    {
    }

    public function testBug()
    {
        $yml = <<<END
---
Account:
  A1:
    Amount: 100
  A2:
    amount: 200
  A3:
    Amount: 300
  A4:
    Entity_id: -100
  A5:
    entity_id: -200
  A6:
    Entity_id: -300
END;

        file_put_contents('test.yml', $yml);
        $import = new \Doctrine1\Data\Import('test.yml');
        $import->setFormat('yml');

        // try to import garbled records (with incorrect field names,
        // e.g. "Amount" instead of "amount") and expect that doctrine
        // will raise an exception.
        $this->expectException(\Doctrine1\Record\UnknownPropertyException::class);
        $import->doImport();
    }
}
