<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket941Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['Site', 'Variable', 'SiteVarvalue'];

        public static function prepareData(): void
        {
            $site              = new \Site();
            $site->site_id     = 1;
            $site->site_domain = 'site1';
            $site->save();

            $site              = new \Site();
            $site->site_id     = 2;
            $site->site_domain = 'site2';
            $site->save();

            $var                = new \Variable();
            $var->variable_id   = 1;
            $var->variable_name = 'var1';
            $var->save();

            $var                = new \Variable();
            $var->variable_id   = 2;
            $var->variable_name = 'var2';
            $var->save();

            $varval                 = new \SiteVarvalue();
            $varval->site_id        = 1;
            $varval->variable_id    = 1;
            $varval->varvalue_value = 'val1 dom1 var1';
            $varval->save();

            $varval                 = new \SiteVarvalue();
            $varval->site_id        = 1;
            $varval->variable_id    = 2;
            $varval->varvalue_value = 'val2 dom1 var2';
            $varval->save();

            $varval                 = new \SiteVarvalue();
            $varval->site_id        = 2;
            $varval->variable_id    = 1;
            $varval->varvalue_value = 'val3 dom2 var1';
            $varval->save();

            $varval                 = new \SiteVarvalue();
            $varval->site_id        = 2;
            $varval->variable_id    = 2;
            $varval->varvalue_value = 'val4 dom2 var2';
            $varval->save();
        }

        public function testTicket()
        {
            $query = new \Doctrine1\Query();
            $query = $query->from('Site s LEFT JOIN s.Variables v LEFT JOIN v.Values vv WITH vv.site_id = s.site_id');

            $sites = $query->execute();

            $this->assertEquals('site1', $sites[0]->site_domain);
            $this->assertEquals(2, count($sites));

            // this is important for the understanding of the behavior
            $this->assertSame($sites[0]->Variables[0], $sites[1]->Variables[0]);
            $this->assertSame($sites[0]->Variables[1], $sites[1]->Variables[1]);
            $this->assertEquals(2, count($sites[0]->Variables[0]->Values));
            $this->assertEquals(2, count($sites[1]->Variables[0]->Values));
            $this->assertEquals(2, count($sites[0]->Variables[1]->Values));
            $this->assertEquals(2, count($sites[1]->Variables[1]->Values));
            // Here we see that there can be only one Values on each Variable object. Hence
            // they end up with 2 objects each.
            $this->assertEquals('val1 dom1 var1', $sites[0]->Variables[0]->Values[0]->varvalue_value);
            $this->assertEquals('val3 dom2 var1', $sites[0]->Variables[0]->Values[1]->varvalue_value);
            $this->assertEquals('val2 dom1 var2', $sites[0]->Variables[1]->Values[0]->varvalue_value);
            $this->assertEquals('val4 dom2 var2', $sites[0]->Variables[1]->Values[1]->varvalue_value);

            $this->assertEquals('var1', $sites[0]->Variables[0]->variable_name);
            $this->assertEquals('var1', $sites[1]->Variables[0]->variable_name);

            $this->assertEquals('var2', $sites[0]->Variables[1]->variable_name);
            $this->assertEquals('var2', $sites[1]->Variables[1]->variable_name);


            // now array hydration

            $sites = $query->fetchArray();

            $this->assertEquals('site1', $sites[0]['site_domain']);
            $this->assertEquals('site2', $sites[1]['site_domain']);
            $this->assertEquals(2, count($sites));

            // this is important for the understanding of the behavior
            $this->assertEquals(1, count($sites[0]['Variables'][0]['Values']));
            $this->assertEquals(1, count($sites[1]['Variables'][0]['Values']));
            $this->assertEquals(1, count($sites[0]['Variables'][1]['Values']));
            $this->assertEquals(1, count($sites[1]['Variables'][1]['Values']));
            // Here we see that the Values collection of the *same* Variable object can have
            // different contents when hydrating arrays
            $this->assertEquals('val1 dom1 var1', $sites[0]['Variables'][0]['Values'][0]['varvalue_value']);
            $this->assertEquals('val3 dom2 var1', $sites[1]['Variables'][0]['Values'][0]['varvalue_value']);
            // Here we see that the Values collection of the *same* Variable object can have
            // different contents when hydrating arrays
            $this->assertEquals('val2 dom1 var2', $sites[0]['Variables'][1]['Values'][0]['varvalue_value']);
            $this->assertEquals('val4 dom2 var2', $sites[1]['Variables'][1]['Values'][0]['varvalue_value']);

            $this->assertEquals('var1', $sites[0]['Variables'][0]['variable_name']);
            $this->assertEquals('var1', $sites[1]['Variables'][0]['variable_name']);

            $this->assertEquals('var2', $sites[0]['Variables'][1]['variable_name']);
            $this->assertEquals('var2', $sites[1]['Variables'][1]['variable_name']);
        }
    }
}

namespace {

    abstract class BaseSite extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('site');
            $this->hasColumn('site_id', 'integer', 4, ['notnull' => true, 'primary' => true, 'autoincrement' => true]);
            $this->hasColumn('site_domain', 'string', 255, ['notnull' => true]);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany(
                'Variable as Variables',
                ['refClass' => 'SiteVarvalue',
                                                  'local'        => 'site_id',
                'foreign'      => 'variable_id']
            );
        }
    }
    abstract class BaseVariable extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('variable');
            $this->hasColumn('variable_id', 'integer', 4, ['notnull' => true, 'primary' => true, 'autoincrement' => true]);
            $this->hasColumn('variable_name', 'string', 100, ['notnull' => true]);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany(
                'Site as Sites',
                ['refClass' => 'SiteVarvalue',
                                          'local'        => 'variable_id',
                'foreign'      => 'site_id']
            );

            $this->hasMany(
                'SiteVarvalue as Values',
                ['local' => 'variable_id',
                'foreign'  => 'variable_id']
            );
        }
    }
    abstract class BaseSiteVarvalue extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('_site_varvalue');
            $this->hasColumn('varvalue_id', 'integer', 4, ['notnull' => true, 'primary' => true, 'autoincrement' => true]);
            $this->hasColumn('site_id', 'integer', 4, ['notnull' => true]);
            $this->hasColumn('variable_id', 'integer', 4, ['notnull' => true]);
            $this->hasColumn('varvalue_value', 'string', null, ['notnull' => true]);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne(
                'Variable as Variables',
                ['local' => 'variable_id',
                'foreign'   => 'variable_id']
            );
        }
    }
    class Site extends BaseSite
    {
    }
    class Variable extends BaseVariable
    {
    }
    class SiteVarvalue extends BaseSiteVarvalue
    {
    }
}
