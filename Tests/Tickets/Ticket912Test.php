<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket912Test extends DoctrineUnitTestCase
    {

      /**
       * prepareData
       */

        public static function prepareData(): void
        {
            $oResume                                     = new \ticket912_Resume;
            $oResume->title                              = 'titre';
            $oResume->Person = new \ticket912_Person();
            $oResume->Person->name                       = 'David';
            $oResume->KnownLanguages[0]->comments        = 'foo';
            $oResume->KnownLanguages[0]->Language = new \ticket912_Language();
            $oResume->KnownLanguages[0]->Language->label = 'Enlish';
            $oResume->KnownLanguages[0]->Level = new \ticket912_LanguageLevel();
            $oResume->KnownLanguages[0]->Level->label    = 'Fluent';
            $oResume->save();
        }

        /**
         * prepareTables
         */

        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'ticket912_Resume';
            static::$tables[] = 'ticket912_Person';
            static::$tables[] = 'ticket912_ResumeHasLanguage';
            static::$tables[] = 'ticket912_LanguageLevel';
            static::$tables[] = 'ticket912_Language';

            parent :: prepareTables();
        }


        /**
         * Test the existence expected indexes
         */

        public function testTicket()
        {
            $q = new \Doctrine1\Query();

            // simple query with deep relations
            $q->addSelect('Resume.id, Person.id, Person.name, KnownLanguages.id, Level.label, Language.label')
              ->from('ticket912_Resume Resume')
              ->leftJoin('Resume.Person Person')
              ->leftJoin('Resume.KnownLanguages KnownLanguages')
              ->leftJoin('KnownLanguages.Level Level')
              ->leftJoin('KnownLanguages.Language Language');

            $aResult = $q->fetchArray();

            // should be setted..
            $issetLevel    = isset($aResult[0]['KnownLanguages'][0]['Level']);
            $issetLanguage = isset($aResult[0]['KnownLanguages'][0]['Language']);

            $this->assertTrue($issetLevel);
            $this->assertTrue($issetLanguage);
        }
    }
}

namespace {
    class ticket912_Resume extends \Doctrine1\Record
    {
        /**
       * setTableDefinition
       */

        public function setTableDefinition(): void
        {
            $this->setTableName('resume');
            $this->hasColumn('id', 'integer', 8, [
              'primary'       => true,
              'autoincrement' => true,
              'unsigned'      => true,
              ]);

            $this->hasColumn('person_id', 'integer', 8, ['unsigned' => true]);
            $this->hasColumn('title', 'string', 255);
        }

        /**
         * setUp
         */

        public function setUp(): void
        {
            $this->hasMany('ticket912_ResumeHasLanguage as KnownLanguages', ['local' => 'id', 'foreign' => 'resume_id']);

            $this->hasOne('ticket912_Person as Person', [
            'local'    => 'person_id',
            'foreign'  => 'id',
            'onDelete' => 'SET NULL',
            'onUpdate' => 'CASCADE'
            ]);
        }
    }

/**
 *  First level one to one relation class Language
 */
    class ticket912_Person extends \Doctrine1\Record
    {
        /**
         * setTableDefinition
         */

        public function setTableDefinition(): void
        {
            $this->setTableName('person');
            $this->hasColumn('id', 'integer', 8, [
            'primary'       => true,
            'autoincrement' => true,
            'unsigned'      => true,
            ]);

            $this->hasColumn('name', 'string', 255, []);
        }
    }


/**
 *  Second level one to many relation class ResumeHasLanguageLanguage
 */

    class ticket912_ResumeHasLanguage extends \Doctrine1\Record
    {
        /**
         * setTableDefinition
         */

        public function setTableDefinition(): void
        {
            $this->setTableName('resume_has_language');
            $this->hasColumn('id', 'integer', 8, [
              'primary'       => true,
              'autoincrement' => true,
              'unsigned'      => true,
              ]);

            $this->hasColumn('resume_id', 'integer', 8, [
              'notnull'  => true,
              'unsigned' => true,
              ]);

            $this->hasColumn('language_id', 'integer', 2, [
            'unsigned' => true,
            ]);

            $this->hasColumn('language_level_id', 'integer', 2, [
            'unsigned' => true,
            ]);

            $this->hasColumn('comments', 'string', 4000, []);
        }

        /**
         * setUp
         */

        public function setUp(): void
        {
            $this->hasOne('ticket912_Resume as Resume', ['local' => 'resume_id',
                                  'foreign'                       => 'id',
                                  'onDelete'                      => 'CASCADE',
                                  'onUpdate'                      => 'CASCADE']);

            $this->hasOne('ticket912_Language as Language', ['local' => 'language_id',
                                    'foreign'                         => 'id',
                                    'onDelete'                        => 'CASCADE',
                                    'onUpdate'                        => 'CASCADE']);

            $this->hasOne('ticket912_LanguageLevel as Level', ['local' => 'language_level_id',
                                                  'foreign'             => 'id',
                                                  'onDelete'            => 'SET NULL',
                                                  'onUpdate'            => 'CASCADE']);
        }
    }



/**
 *  Third level one to one relation class Language
 */
    class ticket912_Language extends \Doctrine1\Record
    {
        /**
         * setTableDefinition
         */

        public function setTableDefinition(): void
        {
            $this->setTableName('language');
            $this->hasColumn('id', 'integer', 2, [
            'primary'       => true,
            'autoincrement' => true,
            'unsigned'      => true,
            ]);

            $this->hasColumn('label', 'string', 100, ['notnull' => true]);
        }

        /**
         * setup
         */

        public function setUp(): void
        {
            $this->hasMany('ticket912_Resume as Resumes', ['local' => 'id', 'foreign' => 'language_id']);
            $this->hasMany('ticket912_ResumeHasLanguage as ResumeKnownLanguages', ['local' => 'id', 'foreign' => 'language_id']);
        }
    }

/**
 * Third level one to one relation class Language
 */

    class ticket912_LanguageLevel extends \Doctrine1\Record
    {
        /**
         * setTableDefinition
         */

        public function setTableDefinition(): void
        {
            $this->setTableName('language_level');
            $this->hasColumn('id', 'integer', 2, [
              'primary'       => true,
              'autoincrement' => true,
              'unsigned'      => true,
              ]);

            $this->hasColumn('label', 'string', 100, ['notnull' => true]);
        }

        /**
         * setUp
         */

        public function setUp(): void
        {
            $this->hasMany('ticket912_ResumeHasLanguage as ResumeKnownLanguages', [
            'local'   => 'id',
            'foreign' => 'language_level_id']);
        }
    }
}
