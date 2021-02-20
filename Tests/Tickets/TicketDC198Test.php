<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC198Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $u                 = new \Ticket_DC198_User();
            $u->name           = 'test';
            $u->email->address = 'foo@bar.com';
            $u->save();

            parent::prepareData();
        }

        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC198_User';
            static::$tables[] = 'Ticket_DC198_Email';

            parent::prepareTables();
        }

        public function testRemoveEmail()
        {
            $u       = \Doctrine_Query::create()->from('Ticket_DC198_User')->fetchOne();
            $u->name = 'test2';
            $u->email->delete();
            $u->refreshRelated('email');

            // The email should be empty
            $uArray = $u->toArray();

            $this->assertFalse(isset($uArray['email']));

            // If I fetch the email I shouldn't find any
            $e = \Doctrine_Query::create()->from('Ticket_DC198_Email')->fetchOne();
            $this->assertFalse($e);
        }
    }
}

namespace {
    class Ticket_DC198_Email extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'user_id',
                'integer',
                null,
                [
                'type' => 'integer',
                ]
            );
            $this->hasColumn(
                'address',
                'string',
                150,
                [
                'type'   => 'string',
                'length' => '150',
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_DC198_User',
                [
                'local'   => 'user_id',
                'foreign' => 'id']
            );
        }
    }

    class Ticket_DC198_User extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'name',
                'string',
                150,
                [
                'type'   => 'string',
                'length' => '150',
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_DC198_Email as email',
                [
                'local'   => 'id',
                'foreign' => 'user_id']
            );
        }
    }
}
