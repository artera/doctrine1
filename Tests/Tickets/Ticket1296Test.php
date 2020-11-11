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

        public function testAddDuplicateOrganisation()
        {
            $this->assertEquals(0, static::$conn->transaction->getTransactionLevel());
            $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());
            static::$conn->beginTransaction();

            $this->assertEquals(1, static::$conn->transaction->getTransactionLevel());
            $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());

            $org       = new \NewTicket_Organization();
            $org->name = 'Inc.';
            try {
                $org->save();
                $this->assertTrue(false, 'Unique violation not reported.');
            } catch (\Exception $e) {
                $this->assertEquals(1, static::$conn->transaction->getTransactionLevel());
                $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());
                static::$conn->rollback();
            }

            $this->assertEquals(0, static::$conn->transaction->getTransactionLevel());
            $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());

            $this->expectException(\Exception::class);

            $this->assertEquals(0, static::$conn->transaction->getTransactionLevel());
            $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());
            static::$conn->commit();

            $this->assertEquals(0, static::$conn->transaction->getTransactionLevel());
            $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());
        }

        public function testAddRole()
        {
            $this->assertEquals(0, static::$conn->transaction->getTransactionLevel());
            $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());

            static::$conn->beginTransaction();
            
            $this->assertEquals(1, static::$conn->transaction->getTransactionLevel());
            $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());

            $r       = new \NewTicket_Role();
            $r->name = 'foo';
            $r->save();
                $this->assertEquals(1, static::$conn->transaction->getTransactionLevel());
                $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());
                $this->assertTrue(is_numeric($r->id));
            
            $this->assertEquals(1, static::$conn->transaction->getTransactionLevel());
                $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());
                static::$conn->commit();
                $this->assertEquals(0, static::$conn->transaction->getTransactionLevel());
                $this->assertEquals(0, static::$conn->transaction->getInternalTransactionLevel());
        }
    }
}

namespace {
    class NewTicket_Organization extends Doctrine_Record
    {
        public function setTableDefinition()
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
        public function setTableDefinition()
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
