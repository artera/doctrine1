<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1044Test extends DoctrineUnitTestCase
    {
        public function testTest()
        {
            static::$tables[] = 'Ticket_1044_User';
            static::$tables[] = 'Ticket_1044_UserProfile';
            parent::prepareTables();
        }
    }
}

namespace {
    class Ticket_1044_User extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('username', 'string', 255);
            $this->hasColumn('password', 'string', 255);
            $this->hasColumn('user_profile_id', 'integer');
        }

        public function setUp()
        {
            $this->hasOne(
                'Ticket_1044_UserProfile as UserProfile',
                ['local'   => 'user_profile_id',
                'foreign' => 'id']
            );
            $this->hasOne(
                'Ticket_1044_UserProfile as UserProfile',
                ['local'    => 'user_profile_id',
                                                                      'foreign'  => 'id',
                'override' => true]
            );
        }
    }

    class Ticket_1044_UserProfile extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 255);
        }
    }
}
