<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class TreeStructureTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        // we don't need the standard tables here
        static::$tables = ['TreeLeaf'];
        parent::prepareTables();
    }

    public static function prepareData(): void
    {
    }

    public function testSelfReferentialRelationship()
    {
        $component = new \TreeLeaf();

        $rel = $component->getTable()->getRelation('Parent');
        $rel = $component->getTable()->getRelation('Children');
    }

    public function testLocalAndForeignKeysAreSetCorrectly()
    {
        $component = new \TreeLeaf();

        $rel = $component->getTable()->getRelation('Parent');
        $this->assertEquals($rel->getLocal(), 'parent_id');
        $this->assertEquals($rel->getForeign(), 'id');

        $rel = $component->getTable()->getRelation('Children');
        $this->assertEquals($rel->getLocal(), 'id');
        $this->assertEquals($rel->getForeign(), 'parent_id');
    }

    public function testTreeLeafRelationships()
    {
        /* structure:
         *
         * o1
         *  -> o2
         *  -> o3
         *
         * o4
         *
         * thus it would be expected that o1 has 2 children and o4 is a
         * leaf with no parents or children.
         */

        $o1       = new \TreeLeaf();
        $o1->name = 'o1';
        $o1->save();

        $o2         = new \TreeLeaf();
        $o2->name   = 'o2';
        $o2->Parent = $o1;
        $o2->save();

        $o3         = new \TreeLeaf();
        $o3->name   = 'o3';
        $o3->Parent = $o1;
        $o3->save();

        //$o1->refresh();

        $o4       = new \TreeLeaf();
        $o4->name = 'o4';
        $o4->save();

        $this->assertFalse(isset($o1->Parent));
        $this->assertTrue(isset($o2->Parent));
        $this->assertTrue($o2->Parent === $o1);
        $this->assertFalse(isset($o4->Parent));

        $this->assertTrue(count($o1->Children) == 2);
        $this->assertTrue(count($o1->get('Children')) == 2);

        $this->assertTrue(count($o4->Children) == 0);
    }
    public function testTreeStructureFetchingWorksWithDql()
    {
        $q = new \Doctrine1\Query();
        $q->select('l.*, c.*')
          ->from('TreeLeaf l, l.Children c')
          ->where('l.parent_id IS NULL')
          ->groupby('l.id, c.id');

        $coll = $q->execute();
    }
}
