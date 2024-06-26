<?php

namespace Tests\Record {
    use Tests\DoctrineUnitTestCase;

    class CascadingDeleteTest extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'ForeignKeyTest';
            static::$tables[] = 'CascadeDelete_HouseOwner';
            static::$tables[] = 'CascadeDelete_House';
            static::$tables[] = 'CascadeDelete_CompositeKeyItem';
            static::$tables[] = 'CascadeDelete_ManyManySideA';
            static::$tables[] = 'CascadeDelete_ManyManySideB';
            static::$tables[] = 'CascadeDelete_ManyManyAToB';
            parent::prepareTables();
        }
        public function testCascadingDeleteEmulation()
        {
            $r                    = new \ForeignKeyTest();
            $r->name              = 'Parent';
            $r->Children[0]->name = 'Child 1';
            $this->assertEquals($r->id, null);
            $this->assertEquals($r->Children[0]->id, null);
            $r->save();

            $this->assertEquals($r->id, 1);
            $this->assertEquals($r->Children[0]->id, 2);

            static::$connection->clear();

            $r = static::$connection->query('FROM ForeignKeyTest');

            $this->assertEquals($r->count(), 2);

            // should delete the first child
            $r[0]->delete();

            $this->assertEquals(\Doctrine1\Record\State::TCLEAN, $r[0]->state());
            $this->assertEquals(\Doctrine1\Record\State::TCLEAN, $r[0]->Children[0]->state());

            static::$connection->clear();

            $r = static::$connection->query('FROM ForeignKeyTest');

            $this->assertEquals($r->count(), 0);
        }

        public function testCascadingDeleteEmulationWithListenerInvocations()
        {
            $cascadeListener = new \CascadeDeleteListener($this);
            static::$conn->getTable('ForeignKeyTest')->addRecordListener($cascadeListener);

            $r                                 = new \ForeignKeyTest();
            $r->name                           = 'Parent';
            $r->Children[0]->name              = 'Child 1';
            $r->Children[0]->Children[0]->name = 'Child 1 Child 1';
            $r->Children[1]->name              = 'Child 2';
            $r->save();

            static::$connection->clear();

            $r = static::$connection->query('FROM ForeignKeyTest');

            $this->assertEquals($r->count(), 4);

            // should delete the children recursively
            $r[0]->delete();

            // validate listener invocations
            $this->assertTrue($cascadeListener->preDeleteInvoked);
            $this->assertEquals(4, $cascadeListener->preDeleteInvocationCount);
            $this->assertTrue($cascadeListener->postDeleteInvoked);
            $this->assertEquals(4, $cascadeListener->postDeleteInvocationCount);
            $cascadeListener->reset();

            static::$connection->clear();

            $r = static::$connection->query('FROM ForeignKeyTest');
            $this->assertEquals($r->count(), 0);
        }

        public function testBidirectionalCascadeDeleteDoesNotCauseInfiniteLoop()
        {
            $house            = new \CascadeDelete_House();
            $house->bathrooms = 4;
            $owner            = new \CascadeDelete_HouseOwner();
            $owner->name      = 'Bill Clinton';
            $owner->house     = $house;
            $house->owner     = $owner;
            $owner->save();

            $this->assertEquals(\Doctrine1\Record\State::CLEAN, $owner->state());
            $this->assertEquals(\Doctrine1\Record\State::CLEAN, $house->state());
            $this->assertTrue($owner->exists());
            $this->assertTrue($house->exists());

            $house->delete();

            $this->assertEquals(\Doctrine1\Record\State::TCLEAN, $owner->state());
            $this->assertEquals(\Doctrine1\Record\State::TCLEAN, $house->state());
            $this->assertFalse($owner->exists());
            $this->assertFalse($house->exists());
        }

        public function testCascadingDeleteInOneToZeroOrOneRelation()
        {
            $owner       = new \CascadeDelete_HouseOwner();
            $owner->name = 'Jeff Bridges';
            $owner->save();
            $owner->delete();
        }

        public function testDeletionOfCompositeKeys()
        {
            $compItem      = new \CascadeDelete_CompositeKeyItem();
            $compItem->id1 = 10;
            $compItem->id2 = 11;
            $compItem->save();
            $compItem->delete();

            $this->assertEquals(\Doctrine1\Record\State::TCLEAN, $compItem->state());
            $this->assertFalse($compItem->exists());
        }

        public function testCascadeDeleteManyMany()
        {
            $a1       = new \CascadeDelete_ManyManySideA();
            $a1->name = 'some';
            $b1       = new \CascadeDelete_ManyManySideB();
            $b1->name = 'other';
            $a1->Bs[] = $b1;
            //$b1->As[] = $a1; <- This causes 2 insertions into the AToB table => BUG

            $a1->save();

            $a1->delete();

            $this->assertEquals(\Doctrine1\Record\State::TCLEAN, $a1->state());
            $this->assertFalse($a1->exists());
            $this->assertEquals(\Doctrine1\Record\State::TCLEAN, $b1->state());
            $this->assertFalse($b1->exists());

            $a1->refreshRelated('assocsA');
            $this->assertEquals(0, count($a1->assocsA));
            $b1->refreshRelated('assocsB');
            $this->assertEquals(0, count($b1->assocsB));
        }
    }
}

namespace {
    /* This listener is used to verify the correct invocations of listeners during the
       delete procedure, as well as to verify the object states at the defined points. */
    class CascadeDeleteListener extends \Doctrine1\Record\Listener
    {
        private $test;
        public $preDeleteInvoked          = false;
        public $preDeleteInvocationCount  = 0;
        public $postDeleteInvoked         = false;
        public $postDeleteInvocationCount = 0;

        public function __construct($test)
        {
            $this->test = $test;
        }

        public function preDelete(\Doctrine1\Event $event): void
        {
            $this->test->assertEquals(\Doctrine1\Record\State::CLEAN, $event->getInvoker()->state());
            $this->preDeleteInvoked = true;
            $this->preDeleteInvocationCount++;
        }

        public function postDelete(\Doctrine1\Event $event): void
        {
            $this->test->assertEquals(\Doctrine1\Record\State::TCLEAN, $event->getInvoker()->state());
            $this->postDeleteInvoked = true;
            $this->postDeleteInvocationCount++;
        }

        public function reset()
        {
            $this->preDeleteInvoked          = false;
            $this->preDeleteInvocationCount  = 0;
            $this->postDeleteInvoked         = false;
            $this->postDeleteInvocationCount = 0;
        }
    }

    /* The following is a typical one-to-one cascade => delete scenario. The association
        is bidirectional, as is the cascade. */

    class CascadeDelete_HouseOwner extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 50);
        }
        public function setUp(): void
        {
            $this->hasOne('CascadeDelete_House as house', [
                'local'   => 'id', 'foreign' => 'owner_id',
                'cascade' => ['delete']]);
        }
    }

    class CascadeDelete_House extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('bathrooms', 'integer', 1);
            $this->hasColumn('owner_id', 'integer', 4);
        }
        public function setUp(): void
        {
            $this->hasOne('CascadeDelete_HouseOwner as owner', [
                'local'   => 'owner_id', 'foreign' => 'id',
                'cascade' => ['delete']]);
        }
    }


    /* The following is just a stand-alone class with a composite-key to test the new
       deletion routines with composite keys. Composite foreign keys are currently not
       supported, so we can't test this class in a cascade => delete scenario. */

    class CascadeDelete_CompositeKeyItem extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id1', 'integer', 4, ['primary' => true]);
            $this->hasColumn('id2', 'integer', 4, ['primary' => true]);
        }
    }


    /* The following is an app-level cascade => delete setup of a many-many association
       Note that such a scenario is very unlikely in the real world and also pretty
       slow. */

    class CascadeDelete_ManyManySideA extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 4);
        }
        public function setUp(): void
        {
            $this->hasMany('CascadeDelete_ManyManySideB as Bs', [
                'local'    => 'a_id', 'foreign' => 'b_id',
                'refClass' => 'CascadeDelete_ManyManyAToB',
                'cascade'  => ['delete']]);

            // overrides the doctrine-generated relation to the association class
            // in order to apply the app-level cascade
            $this->hasMany('CascadeDelete_ManyManyAToB as assocsA', [
                'local'   => 'id', 'foreign' => 'a_id',
                'cascade' => ['delete']]);
        }
    }

    class CascadeDelete_ManyManySideB extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 4);
        }
        public function setUp(): void
        {
            $this->hasMany('CascadeDelete_ManyManySideA as As', [
                'local'    => 'b_id', 'foreign' => 'a_id',
                'refClass' => 'CascadeDelete_ManyManyAToB',
                'cascade'  => ['delete']]);

            // overrides the doctrine-generated relation to the association class
            // in order to apply the app-level cascade
            $this->hasMany('CascadeDelete_ManyManyAToB as assocsB', [
                'local'   => 'id', 'foreign' => 'b_id',
                'cascade' => ['delete']]);
        }
    }

    class CascadeDelete_ManyManyAToB extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('a_id', 'integer', 4, ['primary' => true]);
            $this->hasColumn('b_id', 'integer', 4, ['primary' => true]);
        }
    }
}
