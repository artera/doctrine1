<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1323Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'T1323User';
            static::$tables[] = 'T1323UserReference';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }

        public function resetData()
        {
            $q = \Doctrine_Query::create();
            $q->delete()->from('T1323UserReference')->execute();
            $q = \Doctrine_Query::create();
            $q->delete()->from('T1323User')->execute();

            $m       = new \T1323User();
            $m->name = 'Mother';
            $m->save();
            $f       = new \T1323User();
            $f->name = 'Father';
            $f->save();
            $s       = new \T1323User();
            $s->name = 'Son';
            $s->save();
            $d       = new \T1323User();
            $d->name = 'Daughter';
            $d->save();
            $gf       = new \T1323User();
            $gf->name = 'Grandfather';
            $gf->save();
            $gm       = new \T1323User();
            $gm->name = 'Grandmother';
            $gm->save();

            $f->Children[] = $s;
            $f->Children[] = $d;

            $f->Parents[] = $gf;
            $f->Parents[] = $gm;

            $f->save();

            $m->Children[] = $s;
            $m->Children[] = $d;

            $m->save();
        }

        public function testRelationsAreCorrect()
        {
            $this->resetData();

            $f          = \Doctrine_Core::getTable('T1323User')->findOneByName('Father');
            $childLinks = $f->childLinks;
            $this->assertEquals(2, count($childLinks));
            $this->assertEquals($f->id, $childLinks[0]->parent_id);
            $this->assertEquals($f->id, $childLinks[1]->parent_id);

            $parentLinks = $f->parentLinks;
            $this->assertEquals(2, count($parentLinks));
            $this->assertEquals($f->id, $parentLinks[0]->child_id);
            $this->assertEquals($f->id, $parentLinks[1]->child_id);

            $m          = \Doctrine_Core::getTable('T1323User')->findOneByName('Mother');
            $childLinks = $m->childLinks;
            $this->assertEquals(2, count($childLinks));
            $this->assertEquals($m->id, $childLinks[0]->parent_id);
            $this->assertEquals($m->id, $childLinks[1]->parent_id);

            $parentLinks = $m->parentLinks;
            $this->assertEquals(0, count($parentLinks));

            $s          = \Doctrine_Core::getTable('T1323User')->findOneByName('Son');
            $childLinks = $s->childLinks;
            $this->assertEquals(0, count($childLinks));
            $parentLinks = $s->parentLinks;
            $this->assertEquals(2, count($parentLinks));
            $this->assertEquals($s->id, $parentLinks[0]->child_id);
            $this->assertEquals($s->id, $parentLinks[1]->child_id);

            $d          = \Doctrine_Core::getTable('T1323User')->findOneByName('Daughter');
            $childLinks = $d->childLinks;
            $this->assertEquals(0, count($childLinks));
            $parentLinks = $d->parentLinks;
            $this->assertEquals(2, count($parentLinks));
            $this->assertEquals($d->id, $parentLinks[0]->child_id);
            $this->assertEquals($d->id, $parentLinks[1]->child_id);

            $gm         = \Doctrine_Core::getTable('T1323User')->findOneByName('Grandmother');
            $childLinks = $gm->childLinks;
            $this->assertEquals(1, count($childLinks));
            $this->assertEquals($gm->id, $childLinks[0]->parent_id);
            $parentLinks = $gm->parentLinks;
            $this->assertEquals(0, count($parentLinks));

            $gf         = \Doctrine_Core::getTable('T1323User')->findOneByName('Grandfather');
            $childLinks = $gf->childLinks;
            $this->assertEquals(1, count($childLinks));
            $this->assertEquals($gf->id, $childLinks[0]->parent_id);
            $parentLinks = $gf->parentLinks;
            $this->assertEquals(0, count($parentLinks));
        }

        /**
         * this test will fail
         */
        public function testWithShow()
        {
            $this->resetData();

            \T1323User::showAllRelations();
            $this->runTests();
        }

        /**
         * this test will pass
         */
        public function testWithoutShow()
        {
            $this->resetData();

            $this->runTests();
        }


        public function runTests()
        {

            // change "Father"'s name...
            $f       = \Doctrine_Core::getTable('T1323User')->findOneByName('Father');
            $f->name = 'Dad';
            $f->save();

            /*  just playing; makes no difference:
            remove "Dad"'s relation to "Son"... */
            //$s = \Doctrine_Core::getTable("T1323User")->findOneByName("Son");
            //$f->unlink("Children", array($s->id));
            //$f->save();

            $relations = \Doctrine_Core::getTable('T1323UserReference')->findAll();
            foreach ($relations as $relation) {
                /*  never directly touched any relation; so no user should have
                himself as parent or child */
                $this->assertNotEquals($relation->parent_id, $relation->child_id);
            }
        }
    }
}

namespace {
    class T1323User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 30);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'T1323User as Parents',
                ['local'            => 'child_id',
                                                'foreign'               => 'parent_id',
                                                'refClass'              => 'T1323UserReference',
                                                'refClassRelationAlias' => 'childLinks'
                ]
            );

            $this->hasMany(
                'T1323User as Children',
                ['local'            => 'parent_id',
                                                 'foreign'               => 'child_id',
                                                 'refClass'              => 'T1323UserReference',
                                                 'refClassRelationAlias' => 'parentLinks'
                ]
            );
        }

        /**
         * just a little function to show all users and their relations
         */
        public static function showAllRelations()
        {
            $users = \Doctrine_Core::getTable('T1323User')->findAll();

            foreach ($users as $user) {
                $parents  = $user->Parents;
                $children = $user->Children;
            }
        }
    }

    class T1323UserReference extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('parent_id', 'integer', null, ['primary' => true]);
            $this->hasColumn('child_id', 'integer', null, ['primary' => true]);
        }
    }
}
