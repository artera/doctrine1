<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1622Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'Ticket_1622_User';
            static::$tables[] = 'Ticket_1622_UserReference';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $user       = new \Ticket_1622_User();
            $user->name = 'floriank';
            $user->save();

            $user2            = new \Ticket_1622_User();
            $user2->name      = 'test';
            $user2->parents[] = $user;
            $user2->save();
        }

        public function testUnlink()
        {
            $user  = \Doctrine1\Core::getTable('Ticket_1622_User')->findOneByName('floriank');
            $child = \Doctrine1\Core::getTable('Ticket_1622_User')->findOneByName('test');

            $user->unlink('children', $child->id);

            $this->assertTrue($user->hasReference('children'));
            $this->assertTrue($user->hasRelation('children'));
            $this->assertEquals(count($user->children), 0);

            $user->save();

            $user->refresh();
            $user = \Doctrine1\Core::getTable('Ticket_1622_User')->findOneByName('floriank');
            $this->assertEquals(count($user->children), 0);
        }
    }
}

namespace {
    class Ticket_1622_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 30);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1622_User as parents',
                ['local'                                               => 'parent_id',
                                                'refClass'              => 'Ticket_1622_UserReference',
                                                'foreign'               => 'child_id',
                                                'refClassRelationAlias' => 'childrenLinks'
                                                ]
            );

            $this->hasMany(
                'Ticket_1622_User as children',
                ['local'                                                => 'child_id',
                                                 'foreign'               => 'parent_id',
                                                 'refClass'              => 'Ticket_1622_UserReference',
                                                 'refClassRelationAlias' => 'parentLinks'
                                                 ]
            );
        }
    }

    class Ticket_1622_UserReference extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('parent_id', 'integer', null, ['primary' => true]);
            $this->hasColumn('child_id', 'integer', null, ['primary' => true]);
        }
    }
}
