<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1213Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket1213_Person';
            static::$tables[] = 'Ticket1213_Birthday';
            parent::prepareTables();
        }

        public function testTest()
        {
            $guid                          = md5(microtime());
            $person                        = new \Ticket1213_Person();
            $person->Name                  = 'Frank Zappa ' . time();
            $person->guid                  = $guid;
            $person->Birthday = new \Ticket1213_Birthday();
            $person->Birthday->Bday        = '1940-12-21';
            $person->Birthday->person_guid = $guid;
            $person->save();
            $this->assertEquals($person->guid, $guid);
            $this->assertEquals($person->Birthday->person_guid, $guid);
        }
    }
}

namespace {
    class Ticket1213_Birthday extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('person_guid', 'string', 32, ['primary' => true]);
            $this->hasColumn('Bday', 'timestamp');

            $this->index('person_guid', ['fields' => ['person_guid']]);
        }
    }

    class Ticket1213_Person extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('guid', 'string', 32, ['primary' => true]);
            $this->hasColumn('Name', 'string', 100);

            $this->index('guid', ['fields' => ['guid']]);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket1213_Birthday as Birthday',
                ['local'      => 'guid',
                                                                         'foreign'    => 'person_guid',
                'owningSide' => true]
            );
        }
    }
}
