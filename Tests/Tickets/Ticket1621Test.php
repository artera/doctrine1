<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1621Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'Ticket_1621_User';
            static::$tables[] = 'Ticket_1621_UserReference';
            static::$tables[] = 'Ticket_1621_UserReferenceFriends';
            static::$tables[] = 'Ticket_1621_EmailAdresses';
            static::$tables[] = 'Ticket_1621_Group';
            static::$tables[] = 'Ticket_1621_GroupUser';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }

        public function testRelationAliases()
        {
            // this should go to $this->prepareData(), but i need
            // it to fail in a test()-method
            $group       = new \Ticket_1621_Group();
                $group->name = 'group1';
                $group->save();

                $group2       = new \Ticket_1621_Group();
                $group2->name = 'group2';
                $group2->save();

                $user                             = new \Ticket_1621_User();
                $user->name                       = 'floriank';
                $user->groups[]                   = $group;
                $user->emailAddresses[0]->address = 'floriank@localhost';
                $user->save();

                $user2                             = new \Ticket_1621_User();
                $user2->name                       = 'test2';
                $user2->emailAddresses[0]->address = 'test2@localhost';
                $user2->save();

                $user3                             = new \Ticket_1621_User();
                $user3->name                       = 'test3';
                $user3->emailAddresses[0]->address = 'test3@localhost';
                $user3->save();

                $user4                             = new \Ticket_1621_User();
                $user4->name                       = 'test';
                $user4->groups[]                   = $group2;
                $user4->children[]                 = $user2;
                $user4->parents[]                  = $user3;
                $user4->emailAddresses[0]->address = 'test@localhost';
                $user4->save();

//here is the testcode
            $user      = \Doctrine_Core::getTable('Ticket_1621_User')->findOneByName('floriank');
                $newChild  = \Doctrine_Core::getTable('Ticket_1621_User')->findOneByName('test');
                $newFriend = \Doctrine_Core::getTable('Ticket_1621_User')->findOneByName('test2');
                $newGroup  = \Doctrine_Core::getTable('Ticket_1621_Group')->findOneByName('group2');

                $user->children[] = $newChild;
                $user->groups[]   = $newGroup;
                $user->friends[]  = $newFriend;

                $user->save();

                $this->assertEquals(count($user->children), 1);
        }
    }
}

namespace {
    class Ticket_1621_User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 30);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1621_User as parents',
                ['local'                                               => 'parentId',
                                                'refClass'              => 'Ticket_1621_UserReference',
                                                'foreign'               => 'childId',
                                                'refClassRelationAlias' => 'childrenLinks'
                                                ]
            );

            $this->hasMany(
                'Ticket_1621_User as children',
                ['local'                                                => 'childId',
                                                 'foreign'               => 'parentId',
                                                 'refClass'              => 'Ticket_1621_UserReference',
                                                 'refClassRelationAlias' => 'parentLinks'
                                                 ]
            );

            $this->hasMany(
                'Ticket_1621_User as friends',
                ['local'                                                => 'leftId',
                                                 'foreign'               => 'rightId',
                                                 'equal'                 => 'true',
                                                 'refClass'              => 'Ticket_1621_UserReferenceFriends',
                                                 'refClassRelationAlias' => 'friendLinks'
                                                 ]
            );

            $this->hasMany('Ticket_1621_EmailAdresses as emailAddresses', ['local' => 'id', 'foreign' => 'userId']);

            $this->hasMany(
                'Ticket_1621_Group as groups',
                ['local' => 'userId',
                                     'foreign'                      => 'groupId',
                'refClass'                     => 'Ticket_1621_GroupUser']
            );
        }
    }

    class Ticket_1621_UserReference extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('parent_id as parentId', 'integer', null, ['primary' => true]);
            $this->hasColumn('child_id as childId', 'integer', null, ['primary' => true]);
        }
    }

    class Ticket_1621_UserReferenceFriends extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('left_id as leftId', 'integer', null, ['primary' => true]);
            $this->hasColumn('right_id as rightId', 'integer', null, ['primary' => true]);
        }
    }

    class Ticket_1621_EmailAdresses extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id as userId', 'integer', null);
            $this->hasColumn('address', 'string', 30);

            $this->getTable()->type = 'INNODB';
            $this->getTable()->collate = 'utf8_unicode_ci';
            $this->getTable()->charset = 'utf8';
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_1621_User as user', ['local' => 'userId', 'foreign' => 'id']);
        }
    }

    class Ticket_1621_Group extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 30);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1621_User as users',
                ['local' => 'groupId',
                                     'foreign'                    => 'userId',
                'refClass'                   => 'Ticket_1621_GroupUser']
            );

            $this->setTableName('my_group');
        }
    }

    class Ticket_1621_GroupUser extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('user_id as userId', 'integer', null, ['primary' => true]);
            $this->hasColumn('group_id as groupId', 'integer', null, ['primary' => true]);
        }
    }
}
