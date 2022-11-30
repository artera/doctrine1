<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class CheckTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public static function prepareTables(): void
    {
    }

    public function testCheckParserSupportsStandardFunctions()
    {
        $q = new \Doctrine1\Query\Check('User');

        $q->parse('LENGTH(name) > 6');

        $this->assertEquals($q->getSql(), 'LENGTH(name) > 6');
    }

    public function testCheckParserThrowsExceptionForUnknownOperator()
    {
        $q = new \Doctrine1\Query\Check('User');

        $this->expectException(\Doctrine1\Query\Exception::class);
        $q->parse('LENGTH(name) ? 6');
    }

    public function testCheckParserThrowsExceptionForUnknownFunction()
    {
        $q = new \Doctrine1\Query\Check('User');

        $this->expectException(\Doctrine1\Query\Exception::class);
        $q->parse('SomeUnknownFunction(name) = 6');
    }
}
