<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class SelectExpressionTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['User'];

    public function testAdditionExpression()
    {
        $query = new \Doctrine_Query();
        $query->select('u.*, (u.id + u.id) addition');
        $query->from('User u');

        $users = $query->execute();
    }

    public function testSubtractionExpression()
    {
        $query = new \Doctrine_Query();
        $query->select('u.*, (u.id - u.id) subtraction');
        $query->from('User u');

        $users = $query->execute();
    }

    public function testDivisionExpression()
    {
        $query = new \Doctrine_Query();
        $query->select('u.*, (u.id/u.id) division');
        $query->from('User u');

        $users = $query->execute();
    }

    public function testMultiplicationExpression()
    {
        $query = new \Doctrine_Query();
        $query->select('u.*, (u.id * u.id) multiplication');
        $query->from('User u');

        $users = $query->execute();
    }

    public function testOrderByExpression()
    {
        $query = new \Doctrine_Query();
        $query->select('u.*, (u.id * u.id) multiplication');
        $query->from('User u');
        $query->orderby('multiplication asc');

        $users = $query->execute();
    }
}
