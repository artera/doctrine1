<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1381Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'T1381_Comment';
            static::$tables[] = 'T1381_Article';

            parent::prepareTables();
        }


        public static function prepareData(): void
        {
            $a        = new \T1381_Article();
            $a->title = 'When cleanData worked as expected!';
            $a->save();

            $c             = new \T1381_Comment();
            $c->article_id = $a->id;
            $c->body       = 'Yeah! It will work one day.';
            $c->save();

            $c             = new \T1381_Comment();
            $c->article_id = $a->id;
            $c->body       = 'It will!';
            $c->save();

            // Cleaning up IdentityMap
            \Doctrine_Core::getTable('T1381_Article')->clear();
            \Doctrine_Core::getTable('T1381_Comment')->clear();
        }

        public function testTicket()
        {
            // Now we fetch with data we want (it seems it overrides calculates columns of already fetched objects)
                $dql   = 'SELECT c.*, a.* FROM T1381_Comment c INNER JOIN c.T1381_Article a';
                $items = \Doctrine_Query::create()->query($dql, [], \Doctrine_Core::HYDRATE_ARRAY);

                // This should result in false, since we didn't fetch for this column
                $this->assertFalse(array_key_exists('ArticleTitle', $items[0]['T1381_Article']));

                // We fetch for data including new \columns
                $dql     = 'SELECT c.*, a.title as ArticleTitle FROM T1381_Comment c INNER JOIN c.T1381_Article a WHERE c.id = ?';
                $items   = \Doctrine_Query::create()->query($dql, [1], \Doctrine_Core::HYDRATE_ARRAY);
                $comment = $items[0];

                $this->assertTrue(array_key_exists('ArticleTitle', $comment));
        }


        public function testTicketInverse()
        {
            // We fetch for data including new \columns
                $dql     = 'SELECT c.*, a.title as ArticleTitle FROM T1381_Comment c INNER JOIN c.T1381_Article a WHERE c.id = ?';
                $items   = \Doctrine_Query::create()->query($dql, [1], \Doctrine_Core::HYDRATE_ARRAY);
                $comment = $items[0];

                $this->assertTrue(array_key_exists('ArticleTitle', $comment));

                // Now we fetch with data we want (it seems it overrides calculates columns of already fetched objects)
                $dql   = 'SELECT c.*, a.* FROM T1381_Comment c INNER JOIN c.T1381_Article a';
                $items = \Doctrine_Query::create()->query($dql, [], \Doctrine_Core::HYDRATE_ARRAY);

                // This should result in false, since we didn't fetch for this column
                $this->assertFalse(array_key_exists('ArticleTitle', $items[0]['T1381_Article']));

                // Assert that our existent component still has the column, even after new \hydration on same object
                $this->assertTrue(array_key_exists('ArticleTitle', $comment));

                // Fetch including new \columns again
                $dql   = 'SELECT c.id, a.*, a.id as ArticleTitle FROM T1381_Comment c INNER JOIN c.T1381_Article a';
                $items = \Doctrine_Query::create()->query($dql, [], \Doctrine_Core::HYDRATE_ARRAY);

                // Assert that new \calculated column with different content do not override the already fetched one
                $this->assertTrue(array_key_exists('ArticleTitle', $items[0]));

                // Assert that our existent component still has the column, even after new \hydration on same object
                $this->assertTrue(array_key_exists('ArticleTitle', $comment));
        }
    }
}

namespace {
    class T1381_Article extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('title', 'string', 255, ['notnull' => true]);
        }

        public function setUp()
        {
            $this->hasMany(
                'T1381_Comment',
                [
                'local'   => 'id',
                'foreign' => 'article_id'
                ]
            );
        }
    }


    class T1381_Comment extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('id', 'integer', null, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('body', 'string', null, ['notnull' => true]);
            $this->hasColumn('article_id', 'integer', null, ['notnull' => true]);
        }

        public function setUp()
        {
            $this->hasOne(
                'T1381_Article',
                [
                'local'   => 'article_id',
                'foreign' => 'id'
                ]
            );
        }
    }
}
