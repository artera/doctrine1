<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1254Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'RelX';
            static::$tables[] = 'RelY';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            \Doctrine1\Manager::getInstance()->getCurrentConnection()->beginTransaction();

            $cats = ['cat1', 'cat2'];
            $now  = time();

            for ($i = 0; $i < 10; $i++) {
                $age         = $now - $i * 1000;
                $age         = new \DateTimeImmutable("@$age");
                $x           = new \RelX();
                $x->name     = "x $i";
                $x->category = $cats[$i % 2];
                $x->set('created_at', $age->format('Y-m-d H:i:s'));
                $x->save();

                for ($j = 0; $j < 10; $j++) {
                    $y           = new \RelY();
                    $y->name     = 'y ' . ($i * 10 + $j);
                    $y->rel_x_id = $x->id;
                    $y->save();
                }
            }

            \Doctrine1\Manager::getInstance()->getCurrentConnection()->commit();
        }

        public function testSubqueryExtractionUsesWrongAliases()
        {
            $q = new \Doctrine1\Query();
            $q->from('RelX x');
            $q->leftJoin('x.y xy');
            $q->where('x.created_at IN (SELECT MAX(x2.created_at) latestInCategory FROM RelX x2 WHERE x.category = x2.category)');
            $q->limit(5);

            //echo $sql = $q->getSqlQuery();
            //    echo $sql;

            $xs = $q->execute();

            // Ticket1254Test : method testSubqueryExtractionUsesWrongAliases failed on line 76
            // This fails sometimes at
            $this->assertEquals(2, count($xs));
        }
    }
}

namespace {
    class RelX extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('rel_x');
            $this->hasColumn('name', 'string', 25, []);
            $this->hasColumn('category', 'string', 25, []);
            $this->hasColumn('created_at', 'timestamp', null, []);
        }

        public function setUp(): void
        {
            $this->HasMany('RelY as y', ['local' => 'id', 'foreign' => 'rel_x_id']);
        }
    }

    class RelY extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('rel_y');
            $this->hasColumn('name', 'string', 25, []);
            $this->hasColumn('rel_x_id', 'integer', 10, []);
        }

        public function setUp(): void
        {
        }
    }
}
