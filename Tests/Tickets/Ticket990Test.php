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

            $person = \Doctrine1\Core::getTable('Ticket_990_Person')->find($person->id);

            $this->assertEquals('John', $person->firstname);
        }

        public function testDontOverwriteIdentityMap()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_HYDRATE_OVERWRITE, false);

            $user       = \Doctrine1\Core::getTable('User')->createQuery()->fetchOne();
            $user->name = 'test';
            $user       = \Doctrine1\Core::getTable('User')->find($user->id);
            $this->assertEquals($user->name, 'test');


            $person            = new \Ticket_990_Person();
            $person->firstname = 'John';
            $person->save();

            $person->firstname = 'Alice';

            $this->assertEquals(\Doctrine1\Record\State::DIRTY, $person->state());
            $this->assertTrue($person->isModified());
            $this->assertEquals(['firstname' => 'Alice'], $person->getModified());

            $person = \Doctrine1\Core::getTable('Ticket_990_Person')->find($person->id);

            $this->assertEquals('Alice', $person->firstname);
            $this->assertEquals(\Doctrine1\Record\State::DIRTY, $person->state());
            $this->assertTrue($person->isModified());
            $this->assertEquals(['firstname' => 'Alice'], $person->getModified());

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_HYDRATE_OVERWRITE, true);
        }

        public function testRefreshAlwaysOverwrites()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_HYDRATE_OVERWRITE, false);

            $person            = new \Ticket_990_Person();
            $person->firstname = 'John';
            $person->save();

            $person->firstname = 'Alice';

            $person->refresh();

            $this->assertEquals('John', $person->firstname);

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_HYDRATE_OVERWRITE, true);
        }
    }
}

namespace {
    class Ticket_990_Person extends \Doctrine1\Record
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
