<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class ReferenceModelTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        static::$tables   = [];
        static::$tables[] = 'Forum_Category';
        static::$tables[] = 'Forum_Entry';
        static::$tables[] = 'Forum_Board';
        static::$tables[] = 'Forum_Thread';

        parent::prepareTables();
        static::$connection->clear();
    }
    public static function prepareData(): void
    {
    }

    public function testInitializeData()
    {
        static::$connection->clear();
        $query = new \Doctrine_Query(static::$connection);

        $category = new \Forum_Category();

        $category->name                                 = 'Root';
        $category->Subcategory[0]->name                 = 'Sub 1';
        $category->Subcategory[1]->name                 = 'Sub 2';
        $category->Subcategory[0]->Subcategory[0]->name = 'Sub 1 Sub 1';
        $category->Subcategory[0]->Subcategory[1]->name = 'Sub 1 Sub 2';
        $category->Subcategory[1]->Subcategory[0]->name = 'Sub 2 Sub 1';
        $category->Subcategory[1]->Subcategory[1]->name = 'Sub 2 Sub 2';

        static::$connection->flush();
        static::$connection->clear();

        $category = $category->getTable()->find($category->id);

        $this->assertEquals($category->name, 'Root');
        $this->assertEquals($category->Subcategory[0]->name, 'Sub 1');
        $this->assertEquals($category->Subcategory[1]->name, 'Sub 2');
        $this->assertEquals($category->Subcategory[0]->Subcategory[0]->name, 'Sub 1 Sub 1');
        $this->assertEquals($category->Subcategory[0]->Subcategory[1]->name, 'Sub 1 Sub 2');
        $this->assertEquals($category->Subcategory[1]->Subcategory[0]->name, 'Sub 2 Sub 1');
        $this->assertEquals($category->Subcategory[1]->Subcategory[1]->name, 'Sub 2 Sub 2');

        static::$connection->clear();
    }

    public function testSelfReferencingWithNestedOrderBy()
    {
        $query = new \Doctrine_Query();

        $query->from('Forum_Category.Subcategory.Subcategory');
        $query->orderby('Forum_Category.id ASC, Forum_Category.Subcategory.name DESC');

        $coll = $query->execute();

        $category = $coll[0];

        $this->assertEquals($category->name, 'Root');
        $this->assertEquals($category->Subcategory[0]->name, 'Sub 2');
        $this->assertEquals($category->Subcategory[1]->name, 'Sub 1');
        $this->assertEquals($category->Subcategory[1]->Subcategory[0]->name, 'Sub 1 Sub 1');
        $this->assertEquals($category->Subcategory[1]->Subcategory[1]->name, 'Sub 1 Sub 2');
        $this->assertEquals($category->Subcategory[0]->Subcategory[0]->name, 'Sub 2 Sub 1');
        $this->assertEquals($category->Subcategory[0]->Subcategory[1]->name, 'Sub 2 Sub 2');

        static::$connection->clear();
    }
}
