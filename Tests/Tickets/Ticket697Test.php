<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket697Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        protected static array $tables = ['T697_Person', 'T697_User'];

        public function testIdsAreSetWhenSavingSubclassInstancesInCTI()
        {
            $p         = new \T697_Person();
            $p['name'] = 'Rodrigo';
            $p->save();
            $this->assertEquals(1, $p->id);

            $u             = new \T697_User();
            $u['name']     = 'Fernandes';
            $u['password'] = 'Doctrine RULES';
            $u->save();
            $this->assertEquals(2, $u->id);
        }
    }
}

namespace {
    class T697_Person extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 30);
        }
    }

//Class table inheritance
    class T697_User extends T697_Person
    {
        public function setTableDefinition()
        {
            $this->hasColumn('password', 'string', 30);
        }
    }
}
