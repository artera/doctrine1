<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1296Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $org       = new \NewTicket_Organization();
            $org->name = 'Inc.';
            $org->save();
        }

        protected static array $tables = [
                'NewTicket_Organization',
                'NewTicket_Role'
                ];

        public function testAddDuplicateOrganisation(): void
        {
            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::SLEEP());
            static::$conn->beginTransaction();
            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::ACTIVE());

            $org       = new \NewTicket_Organization();
            $org->name = 'Inc.';
            try {
                $org->save();
                $this->assertTrue(false, 'Unique violation not reported.');
            } catch (\Exception $e) {
                $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::ACTIVE());
                static::$conn->rollback();
            }

            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::SLEEP());

            $this->expectException(\Exception::class);

            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::SLEEP());
            static::$conn->commit();

            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::SLEEP());
        }

        public function testAddRole(): void
        {
            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::SLEEP());

            static::$conn->beginTransaction();

            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::ACTIVE());

            $r       = new \NewTicket_Role();
            $r->name = 'foo';
            $r->save();
            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::ACTIVE());
            $this->assertTrue(is_numeric($r->id));

            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::ACTIVE());
            static::$conn->commit();
            $this->assertEquals(static::$conn->transaction->getState(), \Doctrine_Transaction_State::SLEEP());
        }
    }
}

namespace {
    class NewTicket_Organization extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'autoincrement' => true,
                'notnull'       => true,
                'primary'       => true
                ]
            );
            $this->hasColumn(
                'name',
                'string',
                255,
                [
                'notnull' => true,
                'unique'  => true
                ]
            );
        }
    }

    class NewTicket_Role extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'autoincrement' => true,
                'notnull'       => true,
                'primary'       => true
                ]
            );
            $this->hasColumn(
                'name',
                'string',
                30,
                [
                'notnull' => true,
                'unique'  => true
                ]
            );
        }
    }

    class NewTicket_User
    {
        public function addOrganization($name)
        {
        }
    }
}
