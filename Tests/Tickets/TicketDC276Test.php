<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC276Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC276_Post';
            static::$tables[] = 'Ticket_DC276_Comment';
            parent::prepareTables();
        }

        public function testTest()
        {
            $q = \Doctrine_Query::create()
            ->from('Ticket_DC276_Post p, p.Comments c')
            ->select('p.*, c.*, COUNT(c.id) AS comment_count')
            ->groupBy('p.id')
            ->having('comment_count <= p.max_comments');
            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.content AS t__content, t.max_comments AS t__max_comments, t2.id AS t2__id, t2.post_id AS t2__post_id, t2.content AS t2__content, COUNT(t2.id) AS t2__0 FROM ticket__d_c276__post t LEFT JOIN ticket__d_c276__comment t2 ON t.id = t2.post_id GROUP BY t.id HAVING t2__0 <= t.max_comments');
            $q->execute();
        }
    }
}

namespace {
    class Ticket_DC276_Post extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'content',
                'string',
                1000,
                [
                'type'   => 'string',
                'length' => '1000',
                ]
            );
            $this->hasColumn(
                'max_comments',
                'integer',
                null,
                [
                'type' => 'integer',
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasOne(
                'Ticket_DC276_Comment as Comments',
                [
                'local'   => 'id',
                'foreign' => 'post_id']
            );
        }
    }

    class Ticket_DC276_Comment extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn(
                'post_id',
                'integer',
                null,
                [
                'type' => 'integer',
                ]
            );
            $this->hasColumn(
                'content',
                'string',
                100,
                [
                'type'   => 'string',
                'length' => '100',
                ]
            );
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_DC276_Post',
                [
                'local'   => 'post_id',
                'foreign' => 'id']
            );
        }
    }
}
