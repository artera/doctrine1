<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket638Test extends DoctrineUnitTestCase
    {
        public static function prepareData(): void
        {
        }

        protected static array $tables = ['T638_Student', 'T638_Course', 'T638_StudentCourse'];

        protected function newCourse($id, $name)
        {
            $course       = new \T638_Course();
            $course->id   = $id;
            $course->name = $name;
            $course->save();
            return $course;
        }

        protected function newStudent($id, $name)
        {
            $u           = new \T638_Student();
            $u->id       = $id;
            $u->name     = $name;
            $u->group_id = 1;
            $u->save();
            return $u;
        }

        protected function newStudentCourse($student, $course)
        {
            $sc             = new \T638_StudentCourse;
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

            $sc = new \T638_StudentCourse;
            $sc->set('Student', $student1);
            $sc->set('Course', $course1);

            $this->assertNotInstanceOf(\T638_StudentCourse::class, $student1->get('id'), 'Student Id incorrectly replaced!');
            $this->assertEquals($student1->get('id'), '07090002', 'Student Id is not correct after assignment!');

            $this->assertNotInstanceOf(\T638_StudentCourse::class, $course1->get('id'), 'Course Id incorrectly replaced!');
            $this->assertEquals($course1->get('id'), 'MATH001', 'Course Id is not correct after assignment!');

            $this->assertEquals($sc->get('student_id'), '07090002');
            $this->assertEquals($sc->get('course_id'), 'MATH001');
            $this->assertSame($sc->get('Student'), $student1);
            $this->assertSame($sc->get('Course'), $course1);
        }
    }
}

namespace {
    class T638_Student extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('T638_student');

            $this->hasColumn('s_id as id', 'varchar', 30, [  'primary' => true,]);
            $this->hasColumn('s_g_id as group_id', 'varchar', 30, ['notnull' => true]);
            $this->hasColumn('s_name as name', 'varchar', 50, ['notnull' => true]);
        }

        public function setUp(): void
        {
        }
    }

    class T638_Course extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('T638_course');

            $this->hasColumn('c_id as id', 'varchar', 20, [  'primary' => true,]);
            $this->hasColumn('c_name as name', 'varchar', 50, ['notnull' => true]);
        }

        public function setUp(): void
        {
        }

        public function set($fieldName, $value, $load = true, bool $mutators = false)
        {
            parent::set($fieldName, $value, $load);
        }
    }

    class T638_StudentCourse extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('T638_Student_course');

            $this->hasColumn('sc_student_id as student_id', 'varchar', 30, [  'primary' => true,]);
            $this->hasColumn('sc_course_id as course_id', 'varchar', 20, [  'primary' => true,]);
            $this->hasColumn('sc_remark  as remark', 'varchar', 500, ['notnull' => true]);
        }

        public function setUp(): void
        {
            $this->hasOne('T638_Student as Student', ['local' => 'sc_student_id', 'foreign' => 's_id']);
            $this->hasOne('T638_Course as Course', ['local' => 'sc_course_id', 'foreign' => 'c_id']);
        }
    }
}
