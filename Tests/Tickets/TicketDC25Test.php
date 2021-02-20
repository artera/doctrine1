<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class TicketDC25Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_DC25_Article';
            static::$tables[] = 'Ticket_DC25_Tag';
            static::$tables[] = 'Ticket_DC25_ArticleTag';
            parent::prepareTables();
        }

        public function testTest()
        {
            $q = \Doctrine_Core::getTable('Ticket_DC25_Article')
            ->createQuery('a')
            ->leftJoin('a.Tags t1')
            ->leftJoin('a.Tags t2');

            $this->assertEquals($q->getSqlQuery(), 'SELECT t.id AS t__id, t.name AS t__name, t2.id AS t2__id, t2.name AS t2__name, t4.id AS t4__id, t4.name AS t4__name FROM ticket__d_c25__article t LEFT JOIN ticket__d_c25__article_tag t3 ON (t.id = t3.article_id) LEFT JOIN ticket__d_c25__tag t2 ON t2.id = t3.tag_id LEFT JOIN ticket__d_c25__article_tag t5 ON (t.id = t5.article_id) LEFT JOIN ticket__d_c25__tag t4 ON t4.id = t5.tag_id');
        }
    }
}

namespace {
    class Ticket_DC25_Article extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_DC25_Tag as Tags',
                [
                'local'    => 'article_id',
                'foreign'  => 'tag_id',
                'refClass' => 'Ticket_DC25_ArticleTag'
                ]
            );
        }
    }

    class Ticket_DC25_Tag extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'Ticket_DC25_Article as Article',
                [
                'local'    => 'tag_id',
                'foreign'  => 'article_id',
                'refClass' => 'Ticket_DC25_ArticleTag'
                ]
            );
        }
    }

    class Ticket_DC25_ArticleTag extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('article_id', 'integer');
            $this->hasColumn('tag_id', 'integer');
        }
    }
}
