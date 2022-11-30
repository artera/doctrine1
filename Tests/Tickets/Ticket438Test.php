<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket438Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        protected static array $tables = ['T438_Student', 'T438_Course', 'T438_StudentCourse'];

        protected function newCourse($id, $name)
        {
            $course       = new \T438_Course();
            $course->id   = $id;
            $course->name = $name;
            $course->save();
            return $course;
        }

        protected function newStudent($id, $name)
        {
            $u       = new \T438_Student();
            $u->id   = $id;
            $u->name = $name;
            $u->save();
            return $u;
        }

        protected function newStudentCourse($student, $course)
        {
            $sc             = new \T438_StudentCourse;
            $sc->student_id = $student->id;
            $sc->course_id  = $course->id;
            $sc->save();
            return $sc;
        }

        public function testTicket()
        {
            $student1 = $this->newStudent('07090002', 'First Student');
            $course1  = $this->newCourse('MATH001', 'Maths');
            $course2  = $this->newCourse('ENG002', 'English Literature');

            $this->newStudentCourse($student1, $course1);
            $this->newStudentCourse($student1, $course2);


            // 1. Fetch relationship on demand (multiple queries)
            $q = new \Doctrine1\Query();
            $q->from('T438_StudentCourse sc')
            ->where('sc.student_id = ? AND sc.course_id = ?', ['07090002', 'MATH001']);

            $record = $q->execute()->getFirst();
            $this->assertEquals($record->student_id, '07090002');
            $this->assertEquals($record->course_id, 'MATH001');

            $this->assertEquals($record->get('Student')->id, '07090002');
            $this->assertEquals($record->get('Course')->id, 'MATH001');

            // 2. Fetch relationship in single query
            $q    = new \Doctrine1\Query();
            $coll = $q->select('sc.*, s.*, c.*')
            ->from('T438_StudentCourse sc, sc.Student s, sc.Course c')
            ->where('sc.student_id = ? AND sc.course_id = ?', ['07090002', 'MATH001'])
            ->execute();

            $record = $coll->getFirst();
            $this->assertEquals($record->student_id, '07090002');
            $this->assertEquals($record->course_id, 'MATH001');

            $this->assertEquals($record->get('Student')->id, '07090002');
            $this->assertEquals($record->get('Course')->id, 'MATH001');
        }
    }
}

namespace {
    class T438_Student extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('t438_student_record');

            $this->hasColumn('s_id as id', 'varchar', 30, [  'primary' => true,]);
            $this->hasColumn('s_name as name', 'varchar', 50, []);
        }

        public function setUp(): void
        {
            $this->hasMany('T438_Course as StudyCourses', ['refClass' => 'T438_StudentCourse', 'local' => 'sc_student_id', 'foreign' => 'sc_course_id']);
        }
    }


    class T438_Course extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('t438_course');

            $this->hasColumn('c_id as id', 'varchar', 20, [  'primary' => true,]);
            $this->hasColumn('c_name as name', 'varchar', 50, []);
        }

        public function setUp(): void
        {
            $this->hasMany('T438_Student as Students', ['refClass' => 'T438_StudentCourse', 'local' => 'sc_course_id', 'foreign' => 'sc_student_id']);
        }
    }

    class T438_StudentCourse extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('t438_student_course');

            $this->hasColumn('sc_student_id as student_id', 'varchar', 30, [  'primary' => true,]);
            $this->hasColumn('sc_course_id as course_id', 'varchar', 20, [  'primary' => true,]);
            $this->hasColumn('sc_remark  as remark', 'varchar', 500, []);
        }

        public function setUp(): void
        {
            $this->hasOne('T438_Student as Student', ['local' => 'sc_student_id', 'foreign' => 's_id']);
            $this->hasOne('T438_Course as Course', ['local' => 'sc_course_id', 'foreign' => 'c_id']);
        }
    }
}
