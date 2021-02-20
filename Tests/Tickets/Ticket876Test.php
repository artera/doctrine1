<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket876Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['Profile', 'Person', 'sfGuardUser'];

        public static function prepareData(): void
        {
        }

        public function newPerson($name)
        {
            // Creating sfGuardUser
            $guardUser = new \sfGuardUser();

            $guardUser->set('name', $name);

            $guardUser->save();

            // Creating the Person
            $person = new \Person();

            $person->set('name', $name);
            $person->set('sf_guard_user_id', $guardUser['id']);

            $person->save();

            return $person;
        }

        public function newProfile($name, $person)
        {
            $profile = new \Profile();

            $profile->set('name', $name);
            $profile->set('person_id', $person['id']);

            $profile->save();

            return $profile;
        }

        public function testBug()
        {
            $person  = $this->newPerson('Fixe');
            $profile = $this->newProfile('Work', $person);

            $guardUser = $person->get('sfGuardUser');
            $id        = $guardUser->get('id');

            $guardUser->free();

            $query = new \Doctrine_Query();

            $query->select('s.*, p.*, ps.*');
            $query->from('sfGuardUser s');
            $query->innerJoin('s.Person p');
            $query->leftJoin('p.Profiles ps');
            $query->where('s.id = ?', $id);

            $user  = $query->fetchOne();
            $array = $user->toArray(true);

            $this->assertEquals($array['id'], 1);
            $this->assertEquals($array['name'], 'Fixe');
            $this->assertTrue(isset($array['Person']['Profiles'][0]));
        }
    }
}

namespace {
    class Person extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('person');
            $this->hasColumn('id', 'integer', 11, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('sf_guard_user_id', 'integer', 4);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany(
                'Profile as Profiles',
                ['local' => 'id',
                'foreign'   => 'person_id']
            );
            $this->hasOne(
                'sfGuardUser',
                ['local' => 'sf_guard_user_id',
                                       'foreign'   => 'id',
                'onDelete'  => 'CASCADE']
            );
        }
    }

    class Profile extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('profile');
            $this->hasColumn('id', 'integer', 11, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 150);
            $this->hasColumn('person_id', 'integer', 11);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne(
                'Person',
                ['local' => 'person_id',
                'foreign'     => 'id']
            );
        }
    }

    class sfGuardUser extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('sf_guard_user');
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 128, ['notnull' => true, 'unique' => true]);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne(
                'Person',
                ['local' => 'id',
                'foreign'   => 'sf_guard_user_id']
            );
        }
    }
}
