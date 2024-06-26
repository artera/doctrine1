<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket2292Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'mkArticle';
            static::$tables[] = 'mkContent';
            parent::prepareTables();
        }

        public static function prepareData(): void
        {
        }

        public function testOwningSideRelationToArray()
        {
            $article = new \mkArticle();

            $this->assertEquals($article->content->toArray(false), ['id' => null, 'body' => null]);
        }
    }
}

namespace {
    class mkArticle extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('mk_article');
            $this->hasColumn('id', 'integer', 4, ['type' => 'integer', 'autoincrement' => true, 'primary' => true, 'length' => 4]);
            $this->hasColumn('title', 'string', 200);
        }

        public function setUp(): void
        {
            $this->hasOne(
                'mkContent as content',
                ['local'      => 'id',
                                                    'foreign'    => 'id',
                'owningSide' => false]
            );
        }
    }

    class mkContent extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('mk_content');
            $this->hasColumn('id', 'integer', 4, ['type' => 'integer', 'autoincrement' => false, 'primary' => true, 'length' => 4]);
            $this->hasColumn('body', 'string');
        }

        public function setUp(): void
        {
            $this->hasOne(
                'mkArticle as article',
                ['local'      => 'id',
                                                    'foreign'    => 'id',
                'owningSide' => true]
            );
        }
    }
}
