<?php
namespace Tests\Validator {
    use Tests\DoctrineUnitTestCase;

    class ForeignKeysTest extends DoctrineUnitTestCase
    {
        protected static array $tables = ['TestPerson', 'TestAddress'];

        public function testForeignKeyIsValidIfLocalRelationIsSet()
        {
            $person  = new \TestPerson();
            $address = new \TestAddress();

            $address->Person = $person;

            $table  = $address->getTable();
            $errors = $table->validateField('person_id', $address->person_id, $address);

            $this->assertEquals(0, $errors->count());
        }

        public function testForeignKeyIsValidIfForeignRelationIsSet()
        {
            $person               = new \TestPerson();
            $person->Addresses[0] = new \TestAddress();

            $address = $person->Addresses[0];
            $table   = $address->getTable();
            $errors  = $table->validateField('person_id', $address->person_id, $address);

            $this->assertEquals(0, $errors->count());
        }

        public function testSynchronizedForeignKeyIsValidIfLocalRelationIsSet()
        {
            $person = new \TestPerson();
            $person->synchronizeWithArray(['Addresses' => [[]]]);

            $address = $person->Addresses[0];
            $table   = $address->getTable();

            $errors = $table->validateField('person_id', $address->person_id, $address);
            $this->assertEquals(0, $errors->count());
        }

        public function testSynchronizedForeignKeyIsValidIfForeignRelationIsSet()
        {
            $address = new \TestAddress();
            $address->synchronizeWithArray(['Person' => []]);

            $table  = $address->getTable();
            $errors = $table->validateField('person_id', $address->person_id, $address);
            $this->assertEquals(0, $errors->count());
        }
    }
}

namespace {
    class TestPerson extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'notnull' => true, 'autoincrement' => true]);
            $this->hasColumn('first_name', 'string');
            $this->hasColumn('last_name', 'string');
            $this->hasColumn('favorite_color_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasMany('TestAddress as Addresses', ['local' => 'id', 'foreign' => 'person_id']);
        }
    }

    class TestAddress extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'notnull' => true, 'autoincrement' => true]);
            $this->hasColumn('person_id', 'integer', null, ['notnull' => true]);
            $this->hasColumn('street', 'string');
            $this->hasColumn('city', 'string');
            $this->hasColumn('state', 'string');
            $this->hasColumn('zip', 'string');
        }

        public function setUp(): void
        {
            $this->hasOne('TestPerson as Person', ['local' => 'person_id', 'foreign' => 'id']);
        }
    }
}
