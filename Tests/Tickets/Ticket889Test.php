<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket889Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_889';
            static::$tables[] = 'Ticket_889_Relationship';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }

        public function testManyTreeRelationWithSelfRelation_Children()
        {
            $component = new \Ticket_889();

            $rel = $component->getTable()->getRelation('Children');


            $this->assertEquals(get_class($rel), 'Doctrine_Relation_Nest');

            $this->assertTrue($component->Children instanceof \Doctrine_Collection);
            $this->assertTrue($component->Children[0] instanceof \Ticket_889);
        }

        public function testManyTreeRelationWithSelfRelation_Parents()
        {
            $component = new \Ticket_889();

            $rel = $component->getTable()->getRelation('Parents');


            $this->assertEquals(get_class($rel), 'Doctrine_Relation_Nest');

            $this->assertTrue($component->Parents instanceof \Doctrine_Collection);
            $this->assertTrue($component->Parents[0] instanceof \Ticket_889);
        }

        public function testInitData()
        {
            $test             = new \Ticket_889();
            $test->table_name = 'Feature';
            $test->save();

            $test3             = new \Ticket_889();
            $test3->table_name = 'Application';
            $test3->save();

            $test2              = new \Ticket_889();
            $test2->table_name  = 'Module';
            $test2->Children[0] = $test;
            $test2->Parents[0]  = $test3;
            $test2->save();
        }
    }
}

namespace {
    class Ticket_889 extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            // set Table Name
            $this->setTableName('Ticket_889');

            // set table type
            $this->getTable()->type = 'INNODB';

            // set character set
            $this->getTable()->charset = 'utf8';

            // id
            $this->hasColumn(
                'id',
                'integer',
                10,
                [    'primary'            => true,
                        'unsigned'      => true,
                        'autoincrement' => true
                ]
            );

            // table_name
            $this->hasColumn(
                'table_name',
                'string',
                100,
                [    'notnull'       => true,
                        'notblank' => true,
                        'unique'   => true
                ]
            );
        }

        public function setUp(): void
        {
            // Ticket_889_Relationship child_id
            $this->hasMany(
                'Ticket_889 as Parents',
                [    'local'     => 'child_id',
                    'foreign'  => 'parent_id',
                    'refClass' => 'Ticket_889_Relationship'
                ]
            );

            // Ticket_889_Relationship parent_id
            $this->hasMany(
                'Ticket_889 as Children',
                [    'local'     => 'parent_id',
                    'foreign'  => 'child_id',
                    'refClass' => 'Ticket_889_Relationship'
                ]
            );
        }
    }

    class Ticket_889_Relationship extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            // set Table Name
            $this->setTableName('Ticket_889_Relationship');

            // set table type
            $this->getTable()->type = 'INNODB';

            // set character set
            $this->getTable()->charset = 'utf8';

            // parent_id
            $this->hasColumn(
                'parent_id',
                'integer',
                10,
                [     'primary'  => true,
                    'unsigned' => true
                ]
            );

            // child_id
            $this->hasColumn(
                'child_id',
                'integer',
                10,
                [     'primary'  => true,
                    'unsigned' => true
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_889 as Parent',
                ['local'    => 'parent_id',
                                                    'foreign'  => 'id',
                'onDelete' => 'CASCADE']
            );

            $this->hasOne(
                'Ticket_889 as Child',
                ['local'    => 'child_id',
                                                   'foreign'  => 'id',
                'onDelete' => 'CASCADE']
            );
        }
    }
}
