<?php
namespace Tests\Query;

use Illuminate\Database\Query\Expression;
use Tests\DoctrineUnitTestCase;

class BuilderTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    public static function prepareTables(): void
    {
    }

    public function testWhereRaw()
    {
        $sql = static::$connection->iquery()
            ->from('user')
            ->whereRaw('1 = 1 OR 2 = 2')
            ->where('3', '=', '3')
            ->toSql();
        static::assertEquals('select * from "user" where (1 = 1 OR 2 = 2) and "3" = ?', $sql);

        $sql = static::$connection->iquery()
            ->from('user')
            ->whereRaw(new Expression('1 = 1'))
            ->toSql();
        static::assertEquals('select * from "user" where (1 = 1)', $sql);
    }

    public function testWhereExpression()
    {
        $sql = static::$connection->iquery()
            ->from('user')
            ->selectExpression('1 = 1 OR 2 = 2', 'expr')
            ->toSql();
        static::assertEquals('select (1 = 1 OR 2 = 2) as "expr" from "user"', $sql);
    }

    public function testWhereExpressions()
    {
        $sql = static::$connection->iquery()
            ->from('user')
            ->selectExpressions([
                'expr' => '1 = 1 OR 2 = 2',
                'expr2' => new Expression('3 = 3 OR 4 = 4'),
            ])
            ->toSql();
        static::assertEquals('select (1 = 1 OR 2 = 2) as "expr", (3 = 3 OR 4 = 4) as "expr2" from "user"', $sql);
    }
}
