<?php

namespace Tests\Tickets;

use Doctrine1\Column\Type;
use Tests\DoctrineUnitTestCase;

class Ticket2398Test extends DoctrineUnitTestCase
{
    // Since this file is the subject of the test, we need to add some utf-8 chars to mess up
    // the non-binary-safe count.
    private $randomUtf8 = 'øåæØÅÆØÅæøåøæØÅæøåØÆØåøÆØÅøæøåøæøåÅØÆØ';

    public function testIsValidLength()
    {
        $binaryValue = fread(fopen(__FILE__, 'r'), filesize(__FILE__));

        //Should pass with size the same size as maximum size
        $this->assertTrue(\Doctrine1\Validator::validateLength($binaryValue, Type::BLOB, filesize(__FILE__)));

        //Should fail with maximum size 1 less than actual file size
        $this->assertFalse(\Doctrine1\Validator::validateLength($binaryValue, Type::BLOB, filesize(__FILE__) - 1));
    }
}
