<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket2377Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'Ticket_2377_Author';
            static::$tables[] = 'Ticket_2377_Article';
            parent::prepareTables();
        }

        public function testSynchronize()
        {
            $author          = new \Ticket_2377_Author();
                $article         = new \Ticket_2377_Article();
                $article->Author = $author;

                $array = $article->toArray(true);

                $article2 = new \Ticket_2377_Article();
                $article2->synchronizeWithArray($array);

                $this->assertTrue($article2->Author instanceof \Ticket_2377_Author);
        }
    }
}

namespace {
    class Ticket_2377_Author extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('author');
            $this->hasColumn(
                'id',
                'integer',
                2,
                ['type' => 'integer', 'primary' => true, 'autoincrement' => true, 'length' => '2']
            );
            $this->hasColumn(
                'name',
                'string',
                2,
                ['type' => 'string', 'length' => '100']
            );
        }

        public function setUp(): void
        {
            $this->hasMany('Ticket_2377_Article as Article', ['local' => 'id', 'foreign' => 'author_id']);
        }
    }

    class Ticket_2377_Article extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('article');
            $this->hasColumn(
                'id',
                'integer',
                2,
                ['type' => 'integer', 'primary' => true, 'autoincrement' => true, 'length' => '2']
            );
            $this->hasColumn(
                'author_id',
                'integer',
                2,
                ['type' => 'integer', 'unsigned' => true, 'length' => '2']
            );
            $this->hasColumn(
                'content',
                'string',
                100,
                ['type' => 'string', 'length' => '100']
            );
        }

        public function setUp(): void
        {
            $this->hasOne('Ticket_2377_Author as Author', ['local' => 'author_id', 'foreign' => 'id']);
        }
    }
}
