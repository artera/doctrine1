<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket950Test extends DoctrineUnitTestCase
    {
        public function testInit()
        {
            static::$dbh  = new \Doctrine1\Adapter\Mock('mysql');
            static::$conn = \Doctrine1\Manager::getInstance()->openConnection(static::$dbh);
        }

        public function testTest()
        {
            $sql = static::$conn->export->exportClassesSql(['Ticket_950_AdresseRecord','Ticket_950_CountryRecord']);
            $this->assertEquals(count($sql), 3);
            $this->assertEquals($sql[0], 'CREATE TABLE country_record (id BIGINT NOT NULL AUTO_INCREMENT, iso VARCHAR(2) NOT NULL, name VARCHAR(80), printable_name VARCHAR(80), iso3 VARCHAR(3), numcode BIGINT, INDEX iso_idx (iso), PRIMARY KEY(id)) ENGINE = INNODB');
            $this->assertEquals($sql[1], 'CREATE TABLE adresse_record (id BIGINT NOT NULL AUTO_INCREMENT, adresse VARCHAR(255), cp VARCHAR(60), ville VARCHAR(255), pays VARCHAR(2), INDEX pays_idx (pays), PRIMARY KEY(id)) ENGINE = INNODB');
            $this->assertEquals($sql[2], 'ALTER TABLE adresse_record ADD CONSTRAINT adresse_record_pays_country_record_iso FOREIGN KEY (pays) REFERENCES country_record(iso)');
        }
    }
}

namespace {
    class Ticket_950_AdresseRecord extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('adresse_record');
            $this->hasColumn(
                'id',
                'integer',
                20,
                ['notnull' => true,
                                              'primary'       => true,
                'autoincrement' => true]
            );

            $this->hasColumn('adresse', 'string', 255);
            $this->hasColumn('cp', 'string', 60);
            $this->hasColumn('ville', 'string', 255);
            $this->hasColumn('pays', 'string', 2);
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_950_CountryRecord as Country', ['local' => 'pays', 'foreign' => 'iso']);
        }
    }

    class Ticket_950_CountryRecord extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('country_record');
            $this->hasColumn(
                'id',
                'integer',
                11,
                ['notnull' => true,
                                              'primary'       => true,
                'autoincrement' => true]
            );

            $this->hasColumn('iso', 'string', 2, ['notnull' => true]);

            $this->hasColumn('name', 'string', 80);
            $this->hasColumn('printable_name', 'string', 80);
            $this->hasColumn('iso3', 'string', 3);
            $this->hasColumn('numcode', 'integer', 10);
        }
    }
}
