<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC241Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC241_Poll';
            static::$tables[] = 'Ticket_DC241_PollAnswer';
            parent::prepareTables();
        }

        public function testTest()
        {
            $q = \Doctrine1\Query::create()
            ->from('Ticket_DC241_Poll p')
            ->leftJoin('p.Answers pa ON pa.votes = ?', 100)
            ->addWhere('p.id = ?', 200)
            ->addWhere('p.id = ?', 300)
            ->addWhere('p.id = ?', 400)
            ->addWhere('p.id = ?', 400)
            ->groupBy('p.id')
            ->having('p.id > ?', 300)
            ->limit(10);

            $this->assertEquals($q->getCountSqlQuery(), 'SELECT COUNT(*) AS num_results FROM (SELECT t.id FROM ticket__d_c241__poll t LEFT JOIN module_polls_answers m ON (m.votes = ?) WHERE t.id = ? AND t.id = ? AND t.id = ? AND t.id = ? GROUP BY t.id HAVING t.id > ?) dctrn_count_query');

            $q->count();
        }
    }
}

namespace {
    class Ticket_DC241_Poll extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id_category', 'integer', null, ['notnull' => true]);
            $this->hasColumn('question', 'string', 256);
        }

        public function setUp(): void
        {
            $this->hasMany('Ticket_DC241_PollAnswer as Answers', ['local' => 'id', 'foreign' => 'id_poll', 'orderBy' => 'position']);
        }
    }

    class Ticket_DC241_PollAnswer extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('module_polls_answers');

            $this->hasColumn('id_poll', 'integer', null, ['notnull' => true]);
            $this->hasColumn('answer', 'string', 256);
            $this->hasColumn('votes', 'integer', null, ['notnull' => true, 'default' => 0]);
            $this->hasColumn('position', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_DC241_Poll as Poll', ['local' => 'id_poll', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }
}
