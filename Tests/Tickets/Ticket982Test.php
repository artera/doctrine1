<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket982Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['T982_MyModel'];

        public function testCreateData(): void
        {
            $myModelZero           = new \T982_MyModel();
            $myModelZero->id       = 0;
            $myModelZero->parentid = 0;
            $myModelZero->save();
            $this->assertSame(0, $myModelZero->id);

            $myModelOne           = new \T982_MyModel();
            $myModelOne->id       = 1;
            $myModelOne->parentid = 0;
            $myModelOne->save();

            $myModelTwo           = new \T982_MyModel();
            $myModelTwo->id       = 2;
            $myModelTwo->parentid = 1;
            $myModelTwo->save();
        }

        public function testTicket()
        {
            static::$conn->getTable('T982_MyModel')->clear();

            $myModelZero = static::$conn->getTable('T982_MyModel')->find(0);

            $this->assertSame($myModelZero->id, 0);
            $this->assertSame($myModelZero->parentid, 0);
            $this->assertTrue($myModelZero->parent->exists());
            $this->assertTrue(ctype_digit($myModelZero->parent->id));
            $this->assertSame($myModelZero, $myModelZero->parent);
            $this->assertSame($myModelZero->parent->id, 0);
            $this->assertSame($myModelZero->parent->parentid, 0);

            $myModelOne = static::$conn->getTable('T982_MyModel')->find(1);

            $this->assertSame($myModelOne->id, 1);
            $this->assertSame($myModelOne->parentid, 0);
            $this->assertTrue($myModelOne->parent->exists());
            $this->assertTrue(ctype_digit($myModelOne->parent->id));
            $this->assertSame($myModelOne->parent->id, 0);
            $this->assertSame($myModelOne->parent->parentid, 0);

            $myModelTwo = static::$conn->getTable('T982_MyModel')->find(2);

            $this->assertSame($myModelTwo->id, 2);
            $this->assertSame($myModelTwo->parentid, 1);
            $this->assertSame($myModelTwo->parent->id, 1);
            $this->assertSame($myModelTwo->parent->parentid, 0);
        }
    }
}

namespace {
    class T982_MyModel extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'notnull' => true]);
            $this->hasColumn('parentid', 'integer', 4, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->hasOne('T982_MyModel as parent', ['local' => 'parentid', 'foreign' => 'id']);
        }
    }
}
