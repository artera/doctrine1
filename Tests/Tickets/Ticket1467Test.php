<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1467Test extends DoctrineUnitTestCase
    {
        public function testTicket()
        {
            // SELECT picture_id FROM ItemPicture INNER JOIN Puzzle ON ItemPicture.item_id=ItemPuzzle_item_id
            $q = \Doctrine1\Query::create()
            ->select('pic.id')
            ->from('T1467_Picture pic')
            ->innerJoin('pic.Items ite')
            ->innerJoin('ite.Puzzles puz');

            $this->assertEquals($q->getDql(), 'SELECT pic.id FROM T1467_Picture pic INNER JOIN pic.Items ite INNER JOIN ite.Puzzles puz');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id FROM t1467__picture t INNER JOIN t1467__item_picture t3 ON (t.id = t3.picture_id) INNER JOIN t1467__item t2 ON t2.id = t3.item_id INNER JOIN t1467__item_puzzle t5 ON (t2.id = t5.item_id) INNER JOIN t1467__puzzle t4 ON t4.id = t5.puzzle_id');
        }
    }
}

namespace {
    class T1467_Item extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 50);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'T1467_Picture as Pictures',
                [
                'refClass' => 'T1467_ItemPicture',
                'local'    => 'item_id',
                'foreign'  => 'picture_id'
                ]
            );

            $this->hasMany(
                'T1467_Puzzle as Puzzles',
                [
                'refClass' => 'T1467_ItemPuzzle',
                'local'    => 'item_id',
                'foreign'  => 'puzzle_id'
                ]
            );
        }
    }


    class T1467_Picture extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 50);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'T1467_Item as Items',
                [
                'refClass' => 'T1467_ItemPicture',
                'local'    => 'picture_id',
                'foreign'  => 'item_id'
                ]
            );
        }
    }


    class T1467_Puzzle extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 50);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'T1467_Item as Items',
                [
                'refClass' => 'T1467_ItemPicture',
                'local'    => 'puzzle_id',
                'foreign'  => 'item_id'
                ]
            );
        }
    }


    class T1467_ItemPicture extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('item_id', 'integer', null, ['primary' => true]);
            $this->hasColumn('picture_id', 'integer', null, ['primary' => true]);
        }
    }


    class T1467_ItemPuzzle extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('item_id', 'integer', null, ['primary' => true]);
            $this->hasColumn('puzzle_id', 'integer', null, ['primary' => true]);
        }
    }
}
