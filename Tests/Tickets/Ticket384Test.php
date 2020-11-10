<?php
namespace Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket384Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
            $oResume                                     = new \ticket384_Resume;
            $oResume->title                              = 'titre';
            $oResume->KnownLanguages[0]->comments        = 'foo';
            $oResume->KnownLanguages[0]->Language->label = 'Enlish';
            $oResume->KnownLanguages[0]->Level->label    = 'Fluent';
            $oResume->save();
        }

        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'ticket384_Resume';
            static::$tables[] = 'ticket384_ResumeHasLanguage';
            static::$tables[] = 'ticket384_LanguageLevel';
            static::$tables[] = 'ticket384_Language';

            parent :: prepareTables();
        }

        public function testTicket()
        {
            $q = new \Doctrine_Query();

            // simple query with deep relations
            $q->addSelect('Resume.id, Level.id, Level.label')
                ->from('ticket384_Resume Resume')
                ->leftJoin('Resume.KnownLanguages KnownLanguages')
                ->leftJoin('KnownLanguages.Level Level')
                ->leftJoin('KnownLanguages.Language Language');

            $this->expectException(\Doctrine_Query_Exception::class);

            // get the wrong resultset
            $aResult = $q->fetchArray();

            $q->free();

            // now correct
            // we have to select at least KnownLanguages.id in order to get the Levels,
            // which are only reachable through the KnownLanguages, hydrated properly.
            $q = new \Doctrine_Query();
            $q->addSelect('Resume.id, Level.id, Level.label, KnownLanguages.id')
                ->from('ticket384_Resume Resume')
                ->leftJoin('Resume.KnownLanguages KnownLanguages')
                ->leftJoin('KnownLanguages.Level Level')
                ->leftJoin('KnownLanguages.Language Language');

            $aResult = $q->fetchArray();
            // should be setted
            $bSuccess = isset($aResult[0]['KnownLanguages'][0]['Level']);
            $this->assertTrue($bSuccess, 'fetchArray doesnt hydrate nested child relations, if parent doesnt have a column selected');
        }
    }
}

namespace {
    class ticket384_Resume extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('resume');
            $this->hasColumn(
                'id',
                'integer',
                8,
                [
                'primary'       => true,
                'autoincrement' => true,
                'unsigned'      => true,
                ]
            );

            $this->hasColumn('title', 'string', 255);
        }

        public function setUp()
        {
            $this->hasMany('ticket384_ResumeHasLanguage as KnownLanguages', ['local' => 'id', 'foreign' => 'resume_id']);
        }
    }

    class ticket384_ResumeHasLanguage extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('resume_has_language');
            $this->hasColumn(
                'id',
                'integer',
                8,
                [
                'primary'       => true,
                'autoincrement' => true,
                'unsigned'      => true,
                ]
            );

            $this->hasColumn(
                'resume_id',
                'integer',
                8,
                [
                'notnull'  => true,
                'unsigned' => true,
                ]
            );

            $this->hasColumn(
                'language_id',
                'integer',
                2,
                [
                'unsigned' => true,
                ]
            );

            $this->hasColumn(
                'language_level_id',
                'integer',
                2,
                [
                'unsigned' => true,
                ]
            );

            $this->hasColumn('comments', 'string', 4000, []);
        }

        public function setUp()
        {
            $this->hasOne(
                'ticket384_Resume as Resume',
                ['local' => 'resume_id',
                                    'foreign'                       => 'id',
                                    'onDelete'                      => 'CASCADE',
                'onUpdate'                      => 'CASCADE']
            );

            $this->hasOne(
                'ticket384_Language as Language',
                ['local' => 'language_id',
                                        'foreign'                         => 'id',
                                        'onDelete'                        => 'CASCADE',
                'onUpdate'                        => 'CASCADE']
            );

            $this->hasOne(
                'ticket384_LanguageLevel as Level',
                ['local' => 'language_level_id',
                                                    'foreign'             => 'id',
                                                    'onDelete'            => 'SET NULL',
                'onUpdate'            => 'CASCADE']
            );
        }
    }

    class ticket384_Language extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('language');
            $this->hasColumn(
                'id',
                'integer',
                2,
                [
                'primary'       => true,
                'autoincrement' => true,
                'unsigned'      => true,
                ]
            );

            $this->hasColumn('label', 'string', 100, ['notnull' => true]);
        }

        public function setUp()
        {
            $this->hasMany('ticket384_Resume as Resumes', ['local' => 'id', 'foreign' => 'language_id']);
            $this->hasMany('ticket384_ResumeHasLanguage as ResumeKnownLanguages', ['local' => 'id', 'foreign' => 'language_id']);
        }
    }

    class ticket384_LanguageLevel extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->setTableName('language_level');
            $this->hasColumn(
                'id',
                'integer',
                2,
                [
                'primary'       => true,
                'autoincrement' => true,
                'unsigned'      => true,
                ]
            );

            $this->hasColumn('label', 'string', 100, ['notnull' => true]);
        }

        public function setUp()
        {
            $this->hasMany(
                'ticket384_ResumeHasLanguage as ResumeKnownLanguages',
                [
                'local'   => 'id',
                'foreign' => 'language_level_id']
            );
        }
    }
}
