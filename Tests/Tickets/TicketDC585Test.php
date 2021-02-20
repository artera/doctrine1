<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC585Test extends DoctrineUnitTestCase
    {
        public static function setUpBeforeClass(): void
        {
            static::$dbh  = new \Doctrine_Adapter_Mock('mysql');
            static::$conn = \Doctrine_Manager::getInstance()->openConnection(static::$dbh);
            static::$conn->setAttribute(\Doctrine::ATTR_QUOTE_IDENTIFIER, true);
        }

        protected static array $tables = ['DC585Site', 'DC585PlaceholderValues', 'DC585Placeholder', 'DC585Page', 'DC585PagesPlaceholders'];

        public static function prepareData(): void
        {
            $site       = new \DC585Site();
            $site->id   = '1';
            $site->name = 'Test Site';
            $site->save();

            $placeholder              = new \DC585Placeholder();
            $placeholder->id          = 1;
            $placeholder->placeholder = 'test';
            $placeholder->save();
        }

        public function testExtraQuotesArentAddedToIdentifiers()
        {
            $query = \Doctrine_Query::create()
                ->select('pv.value as value, p.placeholder as placeholder, pp.page_id as page_id')
                ->from('DC585PlaceholderValues pv')
                ->leftJoin('pv.DC585Placeholder p')
                ->leftJoin('p.DC585PagesPlaceholders pp')
                ->andWhere('pv.site_id = ?', 1);

            // We want to make sure that triple back ticks aren't present
            // as that was the side effect of the original change made for DC585
            // for this particular query.  Not checking the exact result of the query
            // as it's a more fragile test case in the event that the order of parameters
            // selected is ever changed in doctrine core
            $this->assertFalse(
                strpos($query->getSqlQuery(), '```')
            );
        }
    }
}

namespace {
    class DC585Site extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('Sites');
            $this->hasColumn(
                'id',
                'string',
                8,
                [
                'type'          => 'string',
                'fixed'         => 0,
                'unsigned'      => false,
                'primary'       => true,
                'autoincrement' => false,
                'length'        => '8',
                ]
            );
            $this->hasColumn(
                'name',
                'string',
                255,
                [
                'type'          => 'string',
                'fixed'         => 0,
                'unsigned'      => false,
                'primary'       => false,
                'notnull'       => true,
                'autoincrement' => false,
                'length'        => '255',
                ]
            );
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany(
                'DC585PlaceholderValues as Placeholders',
                [
                'local'   => 'id',
                'foreign' => 'site_id']
            );
        }
    }

    class DC585PlaceholderValues extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('Placeholders_Values');
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'type'          => 'integer',
                'fixed'         => 0,
                'unsigned'      => true,
                'primary'       => true,
                'autoincrement' => true,
                'length'        => '4',
                ]
            );
            $this->hasColumn(
                'site_id',
                'string',
                8,
                [
                'type'          => 'string',
                'fixed'         => 0,
                'unsigned'      => false,
                'primary'       => false,
                'notnull'       => true,
                'autoincrement' => false,
                'length'        => '8',
                ]
            );
            $this->hasColumn(
                'placeholder_id',
                'integer',
                4,
                [
                'type'          => 'integer',
                'fixed'         => 0,
                'unsigned'      => true,
                'primary'       => false,
                'notnull'       => true,
                'autoincrement' => false,
                'length'        => '4',
                ]
            );
            $this->hasColumn(
                'value',
                'string',
                null,
                [
                'type'          => 'string',
                'fixed'         => 0,
                'unsigned'      => false,
                'primary'       => false,
                'notnull'       => true,
                'autoincrement' => false,
                'length'        => '',
                ]
            );
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne(
                'DC585Site',
                [
                'local'   => 'site_id',
                'foreign' => 'id']
            );

            $this->hasOne(
                'DC585Placeholder',
                [
                'local'   => 'placeholder_id',
                'foreign' => 'id']
            );
        }
    }

    class DC585Page extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('Pages');
            $this->hasColumn(
                'id',
                'string',
                8,
                [
                'type'          => 'string',
                'fixed'         => 0,
                'unsigned'      => false,
                'primary'       => true,
                'autoincrement' => false,
                'length'        => '8',
                ]
            );
            $this->hasColumn(
                'name',
                'string',
                255,
                [
                'type'          => 'string',
                'fixed'         => 0,
                'unsigned'      => false,
                'primary'       => false,
                'notnull'       => true,
                'autoincrement' => false,
                'length'        => '255',
                ]
            );
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany(
                'DC585Placeholder as Placeholders',
                [
                'refClass' => 'PagesPlaceholders',
                'local'    => 'page_id',
                'foreign'  => 'placeholder_id']
            );
        }
    }

    class DC585Placeholder extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('PlaceholderKeys');
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'type'          => 'integer',
                'fixed'         => 0,
                'unsigned'      => true,
                'primary'       => true,
                'autoincrement' => true,
                'length'        => '4',
                ]
            );
            $this->hasColumn(
                'placeholder',
                'string',
                255,
                [
                'type'          => 'string',
                'fixed'         => 0,
                'unsigned'      => false,
                'primary'       => false,
                'notnull'       => true,
                'autoincrement' => false,
                'length'        => '255',
                ]
            );
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany(
                'DC585Page',
                [
                'refClass' => 'DC585PagesPlaceholders',
                'local'    => 'placeholder_id',
                'foreign'  => 'page_id']
            );

            $this->hasMany(
                'DC585PlaceholderValues',
                [
                'local'   => 'id',
                'foreign' => 'placeholder_id']
            );
        }
    }

    class DC585PagesPlaceholders extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('Pages_Placeholders');
            $this->hasColumn(
                'id',
                'integer',
                10,
                [
                'type'          => 'integer',
                'fixed'         => 0,
                'unsigned'      => true,
                'primary'       => true,
                'autoincrement' => true,
                'length'        => '10',
                ]
            );
            $this->hasColumn(
                'page_id',
                'string',
                8,
                [
                'type'          => 'string',
                'fixed'         => 0,
                'unsigned'      => false,
                'primary'       => true,
                'autoincrement' => false,
                'length'        => '8',
                ]
            );
            $this->hasColumn(
                'placeholder_id',
                'integer',
                4,
                [
                'type'          => 'integer',
                'fixed'         => 0,
                'unsigned'      => true,
                'primary'       => false,
                'notnull'       => true,
                'autoincrement' => false,
                'length'        => '4',
                ]
            );
        }

        public function setUp(): void
        {
            parent::setUp();
        }
    }
}
