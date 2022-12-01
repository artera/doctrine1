<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket486Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['Country', 'State', 'Resort'];


        public function testCreateData(): void
        {
            // Countries
            $c1 = $this->createCountry('Argentina');
            $c2 = $this->createCountry('Brazil');
            $c3 = $this->createCountry('England');
            $c4 = $this->createCountry('Russia');

            // States
            $s1 = $this->createState($c1, 'Buenos Aires');
            $s2 = $this->createState($c1, 'Chaco');
            $s3 = $this->createState($c1, 'Santa Fé');

            $s4 = $this->createState($c2, 'Rio de Janeiro');
            $s5 = $this->createState($c2, 'São Paulo');

            $s6 = $this->createState($c3, 'Hampshire');
            $s7 = $this->createState($c3, 'Yorkshire');

            $s8 = $this->createState($c4, 'Yamalia');

            // Resorts
            $r1 = $this->createResort($s1, 'Punta del Sol');
            $r2 = $this->createResort($s2, 'Los Chacos');
            $r3 = $this->createResort($s2, 'Cuesta del Sol');

            $r4 = $this->createResort($s4, 'Copacabana Palace');
            $r5 = $this->createResort($s5, 'Anacã');

            $r6 = $this->createResort($s7, 'Inn');

            $r7 = $this->createResort($s8, 'Hilton');
        }


        public function testLimitSubqueryQuoteIdentifier(): void
        {
            // Change the quote identifier
            $curQuoteIdentifier = $this->getConnection()->getQuoteIdentifier();
            $this->getConnection()->setQuoteIdentifier(true);

            $q = \Doctrine1\Query::create()
            ->select('c.id')
            ->from('Country c, c.State.Resort r')
            ->where('r.id = 3')
            ->limit(1);

            $this->assertMatchesSnapshot($q->getSqlQuery());

            // Restoring quote identifier
            $this->getConnection()->setQuoteIdentifier($curQuoteIdentifier);
        }


        public function createCountry($name): \Country
        {
            $tmp = new \Country();
            $tmp->name = $name;
            $tmp->save();

            return $tmp;
        }


        public function createState($country, $name): \State
        {
            $tmp = new \State();
            $tmp->name = $name;
            $tmp->Country = $country;
            $tmp->save();

            return $tmp;
        }


        public function createResort($state, $name): \Resort
        {
            $tmp = new \Resort();
            $tmp->name = $name;
            $tmp->State = $state;
            $tmp->save();

            return $tmp;
        }
    }
}

namespace {
    class Country extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }


        public function setUp(): void
        {
            $this->hasMany('State', ['local' => 'id', 'foreign' => 'country_id']);
        }
    }


    class State extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('country_id', 'integer', 4);
            $this->hasColumn('name', 'string', 255);
        }


        public function setUp(): void
        {
            $this->hasOne('Country', ['local' => 'country_id', 'foreign' => 'id']);
            $this->hasMany('Resort', ['local' => 'id', 'foreign' => 'state_id']);
        }
    }


    class Resort extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('state_id', 'integer', 4);
            $this->hasColumn('name', 'string', 255);
        }


        public function setUp(): void
        {
            $this->hasOne('State', ['local' => 'state_id', 'foreign' => 'id']);
        }
    }
}
