<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1113Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }
        protected static array $tables = ['VIH_Model_Course', 'VIH_Model_Course_Period', 'VIH_Model_Course_SubjectGroup', 'VIH_Model_Subject', 'VIH_Model_Course_SubjectGroup_Subject', 'VIH_Model_Course_Registration', 'VIH_Model_Course_Registration_Subject'];

        public function testSubjectsCanBeRetrievedWhenReopeningTheRegistrationEvenThoughNoSubjectsWasSavedInitally()
        {
            $course1       = new \VIH_Model_Course();
            $course1->navn = 'Course 1';

            $period1         = new \VIH_Model_Course_Period();
            $period1->name   = 'Period 1';
            $period1->Course = $course1;
            $period1->save();

            $group1         = new \VIH_Model_Course_SubjectGroup();
            $group1->name   = 'SubjectGroup 1';
            $group1->Period = $period1;

            $subject1             = new \VIH_Model_Subject();
            $subject1->identifier = 'Subject 1';

            $subject2             = new \VIH_Model_Subject();
            $subject2->identifier = 'Subject 2';

            $group1->Subjects[] = $subject1;
            $group1->Subjects[] = $subject2;

            $group1->save();

            $group1->Subjects[] = $subject1;
            $group1->Subjects[] = $subject2;
            $group1->save();

            $course1->SubjectGroups[] = $group1;

            $course1->save();

            // saved without Subjects
            $registrar         = new \VIH_Model_Course_Registration();
                $registrar->Course = $course1;
                // $registrar->Subjects; // if this is uncommented the test will pass
                $registrar->save();

            $reopend = \Doctrine1\Core::getTable('VIH_Model_Course_Registration')->findOneById($registrar->id);

            $reopend->Subjects[] = $subject1;

            $reopend->save();

            $subject = $reopend->Subjects[0];
                $this->assertTrue(is_object($subject));
                $this->assertEquals('VIH_Model_Subject', get_class($reopend->Subjects[0]));
        }
    }
}

namespace {
    class VIH_Model_Subject extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('identifier', 'string', 255);
            $this->hasColumn('navn', 'string', 255);
            $this->hasColumn('active', 'boolean');
        }

        public function setUp(): void
        {
            $this->hasMany(
                'VIH_Model_Course_SubjectGroup as SubjectGroups',
                [
                'refClass' => 'VIH_Model_Course_SubjectGroup_Subject',
                'local'    => 'subject_id',
                'foreign'  => 'subject_group_id'
                ]
            );

            $this->hasMany(
                'VIH_Model_Course_Registration as Registrations',
                [
                'refClass' => 'VIH_Model_Course_Registration_Subject',
                'local'    => 'subject_id',
                'foreign'  => 'registration_id'
                ]
            );
        }
    }

    class VIH_Model_Course extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('langtkursus');
            $this->hasColumn('navn', 'string', 255);
        }

        public function setUp(): void
        {
            $this->hasMany(
                'VIH_Model_Course_Period as Periods',
                ['local'   => 'id',
                'foreign' => 'course_id']
            );
            $this->hasMany(
                'VIH_Model_Course_SubjectGroup as SubjectGroups',
                ['local'   => 'id',
                'foreign' => 'course_id']
            );
        }
    }

    class VIH_Model_Course_Period extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('course_id', 'integer');
            $this->hasColumn('date_start', 'date');
            $this->hasColumn('date_end', 'date');
        }

        public function setUp(): void
        {
            $this->hasOne('VIH_Model_Course as Course', ['local' => 'course_id', 'foreign' => 'id']);
        }
    }

    class VIH_Model_Course_Registration extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('langtkursus_tilmelding');
            $this->hasColumn('vaerelse', 'integer');
            $this->hasColumn('kursus_id', 'integer');
            $this->hasColumn('adresse_id', 'integer');
            $this->hasColumn('kontakt_adresse_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne(
                'VIH_Model_Course as Course',
                ['local'   => 'kursus_id',
                'foreign' => 'id']
            );

            $this->hasMany(
                'VIH_Model_Subject as Subjects',
                ['refClass' => 'VIH_Model_Course_Registration_Subject',
                                                             'local'     => 'registration_id',
                'foreign'   => 'subject_id']
            );
        }
    }

    class VIH_Model_Course_SubjectGroup extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('name', 'string', 255);
            $this->hasColumn('period_id', 'integer');
            $this->hasColumn('course_id', 'integer');
        }

        public function setUp(): void
        {
            $this->hasOne(
                'VIH_Model_Course_Period as Period',
                ['local'   => 'period_id',
                'foreign' => 'id']
            );

            $this->hasOne(
                'VIH_Model_Course as Course',
                ['local'   => 'course_id',
                'foreign' => 'id']
            );

            $this->hasMany(
                'VIH_Model_Subject as Subjects',
                ['refClass' => 'VIH_Model_Course_SubjectGroup_Subject',
                                                             'local'     => 'subject_group_id',
                'foreign'   => 'subject_id']
            );
        }
    }

    class VIH_Model_Course_SubjectGroup_Subject extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('subject_group_id', 'integer', null, ['primary' => true]);
            $this->hasColumn('subject_id', 'integer', null, ['primary' => true]);
        }
    }

    class VIH_Model_Course_Registration_Subject extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('registration_id', 'integer', null, ['primary' => true]);
            $this->hasColumn('subject_id', 'integer', null, ['primary' => true]);
        }
    }
}
