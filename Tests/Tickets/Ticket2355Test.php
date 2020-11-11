<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket2355Test extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'News';
            static::$tables[] = 'Episode';
            static::$tables[] = 'Writer';
            static::$tables[] = 'WriterEpisode';
            static::$tables[] = 'Director';
            static::$tables[] = 'DirectorEpisode';
            parent::prepareTables();
        }

        public function testImport()
        {
            $yml = <<<END
Director:
  david_nutter:
    name: David Nutter


Writer:
  alfred_gough:
    name: Alfred Gough
  miles_millar:
    name: Miles Millar


News:
  News_1:
    title: Date de retour de Smallville aux Etats-Unis

  News_2:
    title: Audiences de l'épisode 8.22 Doomsday aux Etats-Unis


Episode:
  Episode_101:
    season: 1
    number: 1
    title_us: Pilot
    title_fr: Bienvenue sur Terre
    Directors: [david_nutter]
    Writers: [alfred_gough, miles_millar]
END;
            file_put_contents('test.yml', $yml);
                \Doctrine_Core::loadData('test.yml', true);

                static::$conn->clear();

                $query = new \Doctrine_Query();
                $query->from('Episode e, e.Directors, e.Writers');

                $e = $query->execute();

                $this->assertEquals($e->count(), 1);
                $this->assertEquals($e[0]->season, 1);
                $this->assertEquals($e[0]->number, 1);
                $this->assertEquals($e[0]->title_us, 'Pilot');
                $this->assertEquals($e[0]->title_fr, 'Bienvenue sur Terre');
                $this->assertEquals($e[0]->Directors->count(), 1);
                $this->assertEquals($e[0]->Directors[0]->name, 'David Nutter');
                $this->assertEquals($e[0]->Writers->count(), 2);
                $this->assertEquals($e[0]->Writers[0]->name, 'Alfred Gough');
                $this->assertEquals($e[0]->Writers[1]->name, 'Miles Millar');

                $query = new \Doctrine_Query();
                $query->from('News n');

                $n = $query->execute();

                $this->assertEquals($n->count(), 2);
                $this->assertEquals($n[0]->title, 'Date de retour de Smallville aux Etats-Unis');
                $this->assertEquals($n[1]->title, 'Audiences de l\'épisode 8.22 Doomsday aux Etats-Unis');

                
            unlink('test.yml');
        }
    }
}

namespace {
    class News extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'title',
                'string',
                255,
                [
                'type'     => 'string',
                'notnull'  => true,
                'notblank' => true,
                'length'   => '255',
                ]
            );
        }
    }

    class Episode extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'season',
                'integer',
                1,
                [
                'type'     => 'integer',
                'length'   => 1,
                'notnull'  => true,
                'notblank' => true,
                ]
            );
            $this->hasColumn(
                'number',
                'integer',
                1,
                [
                'type'     => 'integer',
                'length'   => 1,
                'notnull'  => true,
                'notblank' => true,
                ]
            );
            $this->hasColumn(
                'title_us',
                'string',
                100,
                [
                'type'     => 'string',
                'notnull'  => true,
                'notblank' => true,
                'length'   => '100',
                ]
            );
            $this->hasColumn(
                'title_fr',
                'string',
                100,
                [
                'type'   => 'string',
                'length' => '100',
                ]
            );


            $this->index(
                'episode',
                [
                'fields' => [
                0 => 'season',
                1 => 'number',
                ],
                'type' => 'unique',
                ]
            );
        }

        public function setUp()
        {
            $this->hasMany(
                'Writer as Writers',
                [
                'refClass' => 'WriterEpisode',
                'local'    => 'episode_id',
                'foreign'  => 'writer_id']
            );

            $this->hasMany(
                'Director as Directors',
                [
                'refClass' => 'DirectorEpisode',
                'local'    => 'episode_id',
                'foreign'  => 'director_id']
            );

            $this->hasMany(
                'WriterEpisode',
                [
                'local'   => 'id',
                'foreign' => 'episode_id']
            );

            $this->hasMany(
                'DirectorEpisode',
                [
                'local'   => 'id',
                'foreign' => 'episode_id']
            );
        }
    }

    class Writer extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'name',
                'string',
                150,
                [
                'type'     => 'string',
                'notnull'  => true,
                'notblank' => true,
                'unique'   => true,
                'length'   => '150',
                ]
            );
        }

        public function setUp()
        {
            $this->hasMany(
                'Episode',
                [
                'refClass' => 'WriterEpisode',
                'local'    => 'writer_id',
                'foreign'  => 'episode_id']
            );

            $this->hasMany(
                'WriterEpisode',
                [
                'local'   => 'id',
                'foreign' => 'writer_id']
            );
        }
    }

    class WriterEpisode extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'episode_id',
                'integer',
                null,
                [
                'type'    => 'integer',
                'primary' => true,
                ]
            );
            $this->hasColumn(
                'writer_id',
                'integer',
                null,
                [
                'type'    => 'integer',
                'primary' => true,
                ]
            );
        }

        public function setUp()
        {
            $this->hasOne(
                'Writer',
                [
                'local'    => 'writer_id',
                'foreign'  => 'id',
                'onDelete' => 'CASCADE']
            );

            $this->hasOne(
                'Episode',
                [
                'local'    => 'episode_id',
                'foreign'  => 'id',
                'onDelete' => 'CASCADE']
            );
        }
    }

    class Director extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'name',
                'string',
                150,
                [
                'type'     => 'string',
                'notnull'  => true,
                'notblank' => true,
                'unique'   => true,
                'length'   => '150',
                ]
            );
        }

        public function setUp()
        {
            $this->hasMany(
                'Episode',
                [
                'refClass' => 'DirectorEpisode',
                'local'    => 'director_id',
                'foreign'  => 'episode_id']
            );

            $this->hasMany(
                'DirectorEpisode',
                [
                'local'   => 'id',
                'foreign' => 'director_id']
            );
        }
    }

    class DirectorEpisode extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'episode_id',
                'integer',
                null,
                [
                'type'    => 'integer',
                'primary' => true,
                ]
            );
            $this->hasColumn(
                'director_id',
                'integer',
                null,
                [
                'type'    => 'integer',
                'primary' => true,
                ]
            );
        }

        public function setUp()
        {
            $this->hasOne(
                'Director',
                [
                'local'    => 'director_id',
                'foreign'  => 'id',
                'onDelete' => 'CASCADE']
            );

            $this->hasOne(
                'Episode',
                [
                'local'    => 'episode_id',
                'foreign'  => 'id',
                'onDelete' => 'CASCADE']
            );
        }
    }
}
