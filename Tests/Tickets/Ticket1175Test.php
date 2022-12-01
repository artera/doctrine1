<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1175Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'gImage';

            static::$tables[] = 'gUser';
            static::$tables[] = 'gUserImage';
            static::$tables[] = 'gUserFile';

            static::$tables[] = 'gBlog';
            static::$tables[] = 'gBlogImage';
            static::$tables[] = 'gBlogFile';

            parent::prepareTables();
        }

        public function testLeftJoinToInheritanceChildTable()
        {
            $u             = new \gUser();
            $u->first_name = 'Some User';

            $img           = new \gUserImage();
            $img->filename = 'user image 1';
            $u->Images[]   = $img;

            $img           = new \gUserImage();
            $img->filename = 'user image 2';
            $u->Images[]   = $img;
            $u->save();

            $b        = new \gBlog();
            $b->title = 'First Blog';

            $img           = new \gBlogImage();
            $img->filename = 'blog image 1';
            $b->Images[]   = $img;

            $img           = new \gBlogFile();
            $img->filename = 'blog file 1';
            $b->Files[]    = $img;

            $b->save();

            $q = \Doctrine1\Query::create()
                ->from('gUser u')
                ->leftJoin('u.Images i')
                ->leftJoin('u.Files f')
                ->where('u.id = ?', [1]);

            $this->assertEquals($q->getSqlQuery(), 'SELECT g.id AS g__id, g.first_name AS g__first_name, g.last_name AS g__last_name, g2.id AS g2__id, g2.owner_id AS g2__owner_id, g2.filename AS g2__filename, g2.otype AS g2__otype, g3.id AS g3__id, g3.owner_id AS g3__owner_id, g3.filename AS g3__filename, g3.otype AS g3__otype FROM g_user g LEFT JOIN g_image g2 ON g.id = g2.owner_id AND g2.otype = 1 LEFT JOIN g_file g3 ON g.id = g3.owner_id AND g3.otype = 1 WHERE (g.id = ?)');

            $u = $q->fetchOne();

            $this->assertIsObject($u);
            $this->assertCount(2, $u->Images);
        }
    }
}

namespace {
    class gImage extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('owner_id', 'integer', 4);
            $this->hasColumn('filename', 'string', 64);
            $this->hasColumn('otype', 'integer', 4);
            $this->_table->setExportFlags(\Doctrine1\Core::EXPORT_ALL ^ \Doctrine1\Core::EXPORT_CONSTRAINTS);

            $this->setSubClasses(['gUserImage' => ['otype' => 1],'gBlogImage' => ['otype' => 2]]);
        }
    }

    class gUserImage extends gImage
    {
        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne('gUser as User', ['local' => 'owner_id','foreign' => 'id']);
        }
    }

    class gBlogImage extends gImage
    {
        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne('gBlog as Blog', ['local' => 'owner_id','foreign' => 'id']);
        }
    }

    class gFile extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('owner_id', 'integer', 4);
            $this->hasColumn('filename', 'string', 64);
            $this->hasColumn('otype', 'integer', 4);
            $this->_table->setExportFlags(\Doctrine1\Core::EXPORT_ALL ^ \Doctrine1\Core::EXPORT_CONSTRAINTS);

            $this->setSubClasses(['gUserFile' => ['otype' => 1],'gBlogFile' => ['otype' => 2]]);
        }
    }

    class gUserFile extends gFile
    {
        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne('gUser as User', ['local' => 'owner_id','foreign' => 'id']);
        }
    }

    class gBlogFile extends gFile
    {
        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne('gBlog as Blog', ['local' => 'owner_id','foreign' => 'id']);
        }
    }

    class gBlog extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('title', 'string', 128);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany('gBlogImage as Images', ['local' => 'id','foreign' => 'owner_id']);
            $this->hasMany('gBlogFile as Files', ['local' => 'id','foreign' => 'owner_id']);
        }
    }

    class gUser extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('first_name', 'string', 128);
            $this->hasColumn('last_name', 'string', 128);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany('gUserImage as Images', ['local' => 'id','foreign' => 'owner_id']);
            $this->hasMany('gUserFile as Files', ['local' => 'id','foreign' => 'owner_id']);
        }
    }
}
