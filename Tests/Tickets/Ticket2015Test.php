<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket2015Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $deer        = new \mkAnimal();
            $deer->title = 'Cervus Elaphus';
            $deer->save();

            $beech        = new \mkPlant();
            $beech->title = 'Fagus sylvatica';
            $beech->save();
        }

        public function testColumnAggregation()
        {
            $animal = \Doctrine_Core::getTable('mkNode')->findOneById(1);
            $this->assertTrue($animal instanceof \mkAnimal);

            $plant = \Doctrine_Core::getTable('mkOrganism')->findOneById(2);
            $this->assertTrue($plant instanceof \mkPlant);
        }

        protected static array $tables = [
            'mkNode',
            'mkOrganism',
            'mkAnimal'
        ];
    }
}

namespace {
    class mkNode extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('mk_node');
            $this->hasColumn('id', 'integer', 4, ['type' => 'integer', 'autoincrement' => true, 'primary' => true, 'length' => 4]);
            $this->hasColumn('title', 'string', 255);
            $this->hasColumn('type', 'string', 50);
            $this->hasColumn('sub_type', 'string', 50);

            $this->setSubclasses(
                [
                'mkOrganism' => [
                'type' => 'organism'
                ],
                'mkAnimal' => [
                'type'     => 'organism',
                'sub_type' => 'animal'
                ],
                'mkPlant' => [
                'type'     => 'organism',
                'sub_type' => 'plant'
                ]
                ]
            );
        }
    }

    class mkOrganism extends mkNode
    {
        public function setTableDefinition(): void
        {
            parent::setTableDefinition();

            $this->setSubclasses(
                [
                'mkAnimal' => [
                'type'     => 'organism',
                'sub_type' => 'animal'
                ],
                'mkPlant' => [
                'type'     => 'organism',
                'sub_type' => 'plant'
                ]
                ]
            );
        }
    }

    class mkAnimal extends mkOrganism
    {
    }

    class mkPlant extends mkOrganism
    {
    }
}
