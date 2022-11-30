<?php
namespace Tests\Expression;

use Tests\DoctrineUnitTestCase;

class DriverTest extends DoctrineUnitTestCase
{
    protected ?\Doctrine1\Expression\Mock $expr = null;

    public function setUp(): void
    {
        parent::setUp();
        $this->expr = new \Doctrine1\Expression\Mock();
    }

    public function testAvgReturnsValidSql()
    {
        $this->assertEquals($this->expr->avg('id'), 'AVG(id)');
    }

    public function testCountReturnsValidSql()
    {
        $this->assertEquals($this->expr->count('id'), 'COUNT(id)');
    }

    public function testMaxReturnsValidSql()
    {
        $this->assertEquals($this->expr->max('id'), 'MAX(id)');
    }

    public function testMinReturnsValidSql()
    {
        $this->assertEquals($this->expr->min('id'), 'MIN(id)');
    }

    public function testSumReturnsValidSql()
    {
        $this->assertEquals($this->expr->sum('id'), 'SUM(id)');
    }

    public function testRegexpImplementedOnlyAtDriverLevel()
    {
        $this->expectException(\Doctrine1\Expression\Exception::class);
        $this->expr->regexp('[abc]');
    }

    public function testSoundexImplementedOnlyAtDriverLevel()
    {
        $this->expectException(\Doctrine1\Expression\Exception::class);
        $this->expr->soundex('arnold');
    }

    /**
     * TIME FUNCTIONS
     */
    public function testNowReturnsValidSql()
    {
        $this->assertEquals($this->expr->now(), 'NOW()');
    }

    /**
     * STRING FUNCTIONS
     */
    public function testUpperReturnsValidSql()
    {
        $this->assertEquals($this->expr->upper('id', 3), 'UPPER(id)');
    }
    public function testLowerReturnsValidSql()
    {
        $this->assertEquals($this->expr->lower('id'), 'LOWER(id)');
    }
    public function testLengthReturnsValidSql()
    {
        $this->assertEquals($this->expr->length('id'), 'LENGTH(id)');
    }
    public function testLtrimReturnsValidSql()
    {
        $this->assertEquals($this->expr->ltrim('id'), 'LTRIM(id)');
    }
    public function testLocateReturnsValidSql()
    {
        $this->assertEquals($this->expr->locate('id', 3), 'LOCATE(id, 3)');
    }
    public function testConcatReturnsValidSql()
    {
        $this->assertEquals($this->expr->concat('id', 'type'), 'CONCAT(id, type)');
    }
    public function testSubstringReturnsValidSql()
    {
        $this->assertEquals($this->expr->substring('id', 3), 'SUBSTRING(id FROM 3)');

        $this->assertEquals($this->expr->substring('id', 3, 2), 'SUBSTRING(id FROM 3 FOR 2)');
    }

    /**
     * MATH FUNCTIONS
     */
    public function testRoundReturnsValidSql()
    {
        $this->assertEquals($this->expr->round(2.3), 'ROUND(2.3, 0)');

        $this->assertEquals($this->expr->round(2.3, 1), 'ROUND(2.3, 1)');
    }
    public function testModReturnsValidSql()
    {
        $this->assertEquals($this->expr->mod(2, 3), 'MOD(2, 3)');
    }
    public function testSubReturnsValidSql()
    {
        $this->assertEquals($this->expr->sub([2, 3]), '(2 - 3)');
    }
    public function testMulReturnsValidSql()
    {
        $this->assertEquals($this->expr->mul([2, 3]), '(2 * 3)');
    }
    public function testAddReturnsValidSql()
    {
        $this->assertEquals($this->expr->add([2, 3]), '(2 + 3)');
    }
    public function testDivReturnsValidSql()
    {
        $this->assertEquals($this->expr->div([2, 3]), '(2 / 3)');
    }

    /**
     * ASSERT OPERATORS
     */
    public function testEqReturnsValidSql()
    {
        $this->assertEquals($this->expr->eq(1, 1), '1 = 1');
    }
    public function testNeqReturnsValidSql()
    {
        $this->assertEquals($this->expr->neq(1, 2), '1 <> 2');
    }
    public function testGtReturnsValidSql()
    {
        $this->assertEquals($this->expr->gt(2, 1), '2 > 1');
    }
    public function testGteReturnsValidSql()
    {
        $this->assertEquals($this->expr->gte(1, 1), '1 >= 1');
    }
    public function testLtReturnsValidSql()
    {
        $this->assertEquals($this->expr->lt(1, 2), '1 < 2');
    }
    public function testLteReturnsValidSql()
    {
        $this->assertEquals($this->expr->lte(1, 1), '1 <= 1');
    }

    /**
     * WHERE OPERATORS
     */
    public function testNotReturnsValidSql()
    {
        $this->assertEquals($this->expr->not('id'), 'NOT(id)');
    }
    public function testInReturnsValidSql()
    {
        $this->assertEquals($this->expr->in('id', [1, 2]), 'id IN (1, 2)');
    }
    public function testIsNullReturnsValidSql()
    {
        $this->assertEquals($this->expr->isNull('type'), 'type IS NULL');
    }
    public function testIsNotNullReturnsValidSql()
    {
        $this->assertEquals($this->expr->isNotNull('type'), 'type IS NOT NULL');
    }
    public function testBetweenReturnsValidSql()
    {
        $this->assertEquals($this->expr->between('age', 12, 14), 'age BETWEEN 12 AND 14');
    }
}
