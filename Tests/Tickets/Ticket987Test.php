<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket987Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_987_Person';
            parent::prepareTables();
        }

        public function testTest()
        {
            $person            = new \Ticket_987_Person();
            $person->gender    = 'male';
            $person->firstname = 'jon';
            $person->lastname  = 'wage';
            $person->save();

            // creating the query
            $q = \Doctrine_Query::create();
            $q->from('Ticket_987_Person p');

            // creating the view
            $view = new \Doctrine_View($q, 'view_person2person_type');
            $view->create();

            // creating the query
            $q = \Doctrine_Query::create();
            $q->from('Ticket_987_Person p');

            // creating view object for querying
            $view = new \Doctrine_View($q, 'view_person2person_type');

            // executes view
            $coll = $view->execute();

            $this->assertEquals($coll->count(), 1);
        }
    }
}

namespace {
    class Ticket_987_Person extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('person');
            $this->hasColumn('id', 'integer', 11, ['primary' => true, 'notnull' => true, 'autoincrement' => true]);
            $this->hasColumn('gender', 'integer', 1, ['notblank' => true, 'primary' => false, 'notnull' => true, 'autoincrement' => false]);
            $this->hasColumn('firstname', 'string', 30, ['notblank' => true, 'primary' => false, 'notnull' => true, 'autoincrement' => false]);
            $this->hasColumn('lastname', 'string', 30, ['notblank' => true, 'primary' => false, 'notnull' => true, 'autoincrement' => false]);
        }
    }
}
