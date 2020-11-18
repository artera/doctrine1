<?php
namespace Tests\Validator;

use Tests\DoctrineUnitTestCase;

class FutureTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        static::$tables[] = 'ValidatorTest_DateModel';
        parent::prepareTables();
    }

    public static function prepareData(): void
    {
    }

    public function testValidFutureDates()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

        // one year ahead
        $user1        = new \ValidatorTest_DateModel();
        $user1->death = date('Y-m-d', time() + 365 * 24 * 60 * 60);
        $this->assertTrue($user1->trySave());

        // one month ahead
        $user1        = new \ValidatorTest_DateModel();
        $user1->death = date('Y-m-d', time() + 30 * 24 * 60 * 60);
        $this->assertTrue($user1->trySave());

        // one day ahead
        $user1->death = date('Y-m-d', time() + 24 * 60 * 60);
        $this->assertTrue($user1->trySave());

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }

    public function testInvalidFutureDates()
    {
        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

        $user1        = new \ValidatorTest_DateModel();
        $user1->death = date('Y-m-d', 42);
        $this->assertFalse($user1->trySave());

        $user1->death = date('Y-m-d', time());
        $this->assertFalse($user1->trySave());

        $user1->death = date('Y-m-d', time() + 60);
        $this->assertFalse($user1->trySave());

        static::$manager->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
    }
}
