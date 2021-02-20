<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket990Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['Ticket_990_Person'];

        public function testOverwriteIdentityMap()
        {
            $person            = new \Ticket_990_Person();
            $person->firstname = 'John';
            $person->save();

            $person->firstname = 'Alice';

            $person = \Doctrine_Core::getTable('Ticket_990_Person')->find($person->id);

            $this->assertEquals('John', $person->firstname);
        }

        public function testDontOverwriteIdentityMap()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_HYDRATE_OVERWRITE, false);

            $user       = \Doctrine_Core::getTable('User')->createQuery()->fetchOne();
            $user->name = 'test';
            $user       = \Doctrine_Core::getTable('User')->find($user->id);
            $this->assertEquals($user->name, 'test');


            $person            = new \Ticket_990_Person();
            $person->firstname = 'John';
            $person->save();

            $person->firstname = 'Alice';

            $this->assertEquals(\Doctrine_Record::STATE_DIRTY, $person->state());
            $this->assertTrue($person->isModified());
            $this->assertEquals(['firstname' => 'Alice'], $person->getModified());

            $person = \Doctrine_Core::getTable('Ticket_990_Person')->find($person->id);

            $this->assertEquals('Alice', $person->firstname);
            $this->assertEquals(\Doctrine_Record::STATE_DIRTY, $person->state());
            $this->assertTrue($person->isModified());
            $this->assertEquals(['firstname' => 'Alice'], $person->getModified());

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_HYDRATE_OVERWRITE, true);
        }

        public function testRefreshAlwaysOverwrites()
        {
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_HYDRATE_OVERWRITE, false);

            $person            = new \Ticket_990_Person();
            $person->firstname = 'John';
            $person->save();

            $person->firstname = 'Alice';

            $person->refresh();

            $this->assertEquals('John', $person->firstname);

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_HYDRATE_OVERWRITE, true);
        }
    }
}

namespace {
    class Ticket_990_Person extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('person');
            $this->hasColumn('id', 'integer', 11, ['primary' => true, 'notnull' => true, 'autoincrement' => true]);
            $this->hasColumn('firstname', 'string');
            $this->hasColumn('lastname', 'string');
        }
    }
}
