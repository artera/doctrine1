<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1623Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'Ticket_1623_User';
            static::$tables[] = 'Ticket_1623_UserReference';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $firstUser = null;
            $oldUser   = null;

            for ($i = 1; $i <= 20; $i++) {
                $userI       = $user       = new \Ticket_1623_User();
                $userI->name = "test$i";
                for ($j = 1; $j <= 20; $j++) {
                    $userJ             = new \Ticket_1623_User();
                    $userJ->name       = "test$i-$j";
                    $userI->children[] = $userJ;
                    $userJ->save();
                }
                $userI->save();
                $floriankChilds[] = $userI;
            }

            $user       = new \Ticket_1623_User();
            $user->name = 'floriank';
            foreach ($floriankChilds as $child) {
                $user->children[] = $child;
            }
            $user->save();
        }

        public function testPerformance()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);

            $newChild       = new \Ticket_1623_User();
            $newChild->name = 'myChild';
            $newChild->save();

            $user             = \Doctrine1\Core::getTable('Ticket_1623_User')->findOneByName('floriank');
            $user->children[] = $newChild;

            $start = microtime(true);
            $user->save();
            $end  = microtime(true);
            $diff = $end - $start;
            //assuming save() should not take longer than one second
            $this->assertTrue($diff < 1);
        }

        public function testImplicitSave()
        {
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_ALL);
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_CASCADE_SAVES, false);

            $newChild       = new \Ticket_1623_User();
            $newChild->name = 'myGrandGrandChild';

            $user                                       = \Doctrine1\Core::getTable('Ticket_1623_User')->findOneByName('floriank');
            $user->children[0]->children[0]->children[] = $newChild;

            $user->save();

            $user = \Doctrine1\Core::getTable('Ticket_1623_User')->findByName('myGrandGrandChild');
            //as of Doctrine's default behaviour $newChild should have
            //been implicitly saved with $user->save()
            $this->assertEquals($user->count(), 0);

            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_VALIDATE, \Doctrine1\Core::VALIDATE_NONE);
            \Doctrine1\Manager::getInstance()->setAttribute(\Doctrine1\Core::ATTR_CASCADE_SAVES, true);
        }
    }
}

namespace {
    class Ticket_1623_User extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 30);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_1623_User as parents',
                ['local'                                               => 'parentId',
                                                'refClass'              => 'Ticket_1623_UserReference',
                                                'foreign'               => 'childId',
                                                'refClassRelationAlias' => 'childrenLinks'
                                                ]
            );

            $this->hasMany(
                'Ticket_1623_User as children',
                ['local'                                                => 'childId',
                                                 'foreign'               => 'parentId',
                                                 'refClass'              => 'Ticket_1623_UserReference',
                                                 'refClassRelationAlias' => 'parentLinks'
                                                 ]
            );
        }

        protected function validate()
        {
            // lets get some silly load in the validation:
            // we do not want any child or parent to have the name 'caesar'
            $unwantedName = false;
            foreach ($this->children as $child) {
                if ($child->name == 'caesar') {
                    $unwantedName = true;
                }
            }

            foreach ($this->children as $child) {
                if ($child->name == 'caesar') {
                    $unwantedName = true;
                }
            }

            if ($unwantedName) {
                $this->errorStack()->add('children', 'no child should have the name \'caesar\'');
            }
        }
    }

    class Ticket_1623_UserReference extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('parent_id as parentId', 'integer', null, ['primary' => true]);
            $this->hasColumn('child_id as childId', 'integer', null, ['primary' => true]);
        }
    }
}
