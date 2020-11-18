<?php
namespace Tests\Expression;

use Tests\DoctrineUnitTestCase;

class ExpressionTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public function testSavingWithAnExpression()
    {
        $e = new \Doctrine_Expression("CONCAT('some', 'one')");
        $this->assertEquals($e->getSql(), "CONCAT('some', 'one')");

        $u       = new \User();
        $u->name = $e;
        $u->save();
        $u->refresh();
        $this->assertEquals($u->name, 'someone');
    }

    public function testExpressionParserSupportsNumericalClauses()
    {
        $e = new \Doctrine_Expression('1 + 2');
        $this->assertEquals($e->getSql(), '1 + 2');
    }

    public function testExpressionParserSupportsFunctionComposition()
    {
        $e = new \Doctrine_Expression("SUBSTRING(CONCAT('some', 'one'), 0, 3)");
        $this->assertEquals($e->getSql(), "SUBSTR(CONCAT('some', 'one'), 0, 3)");
    }

    public function testExpressionParserSupportsParensInClauses()
    {
        $e = new \Doctrine_Expression("CONCAT('(some)', '(one)')");
        $this->assertEquals($e->getSql(), "CONCAT('(some)', '(one)')");
    }
}
