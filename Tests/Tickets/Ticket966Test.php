<?php

namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket966Test extends DoctrineUnitTestCase
    {
        protected static array $tables = ['Semester', 'Course', 'Weekday', 'CourseWeekday'];

        public static function prepareData(): void
        {
            $semester         = new \Semester();
            $semester['name'] = 'Semester';
            $semester->save();

            foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $name) {
                $weekday         = new \Weekday();
                $weekday['name'] = $name;
                $weekday->save();
            }

            for ($i = 0; $i < 3; $i++) {
                $course             = new \Course();
                $course['name']     = 'Course ' . $i;
                $course['Semester'] = $semester;
                $course->save();
                for ($w = 3; $w < 6; $w++) {
                    $cw               = new \CourseWeekday();
                    $cw['Course']     = $course;
                    $cw['weekday_id'] = $w;
                    $cw->save();
                }
            }
        }

        public function testArrayHydration()
        {
            $query = \Doctrine1\Query::create()
            ->from('Semester s')
            ->leftJoin('s.Courses c')
            ->leftJoin('c.Weekdays cw');

            $semesters = $query->execute([], \Doctrine1\HydrationMode::Array);
            $semester  = $semesters[0];

            $this->assertAllWeekdaysArePopulated($semester);
        }

        public function testObjectHydration()
        {
            $query = \Doctrine1\Query::create()
            ->from('Semester s')
            ->leftJoin('s.Courses c')
            ->leftJoin('c.Weekdays cw');

            $semester = $query->execute()->getFirst();

            $weekdayOids = [];
            foreach ($semester->Courses as $course) {
                foreach ($course->Weekdays as $weekday) {
                    if (!in_array($weekday->getOid(), $weekdayOids)) {
                        $weekdayOids[] = $weekday->getOid();
                    }
                    $this->assertTrue(is_numeric($weekday->id));
                    $this->assertTrue(is_string($weekday->name));
                }
            }
            // should be only 3 weekday objects in total
            $this->assertEquals(3, count($weekdayOids));

            $queryCountBefore = static::$conn->count();
            $this->assertAllWeekdaysArePopulated($semester);
            $this->assertEquals($queryCountBefore, static::$conn->count());
        }

        public function testLazyObjectHydration()
        {
            // clear identity maps to make sure we're starting with a clean plate
            static::$conn->getTable('Course')->clear();
            static::$conn->getTable('Weekday')->clear();
            static::$conn->getTable('Semester')->clear();
            $query = \Doctrine1\Query::create()->from('Semester s');

            $semester         = $query->execute()->getFirst();
            $queryCountBefore = static::$conn->count();
            $this->assertAllWeekdaysArePopulated($semester);
            // expecting 4 additional queries: 1 to fetch the courses for the only semester and
            // 1 for each weekday collection for each of the three courses.
            $this->assertEquals($queryCountBefore + 4, static::$conn->count());
        }

        private function assertAllWeekdaysArePopulated($semester)
        {
            foreach ($semester['Courses'] as $course) {
                foreach ($course['Weekdays'] as $weekday) {
                    $this->assertTrue(is_numeric($weekday['id']));
                    $this->assertTrue(is_string($weekday['name']));
                }
            }
        }
    }
}

namespace {
    class Semester extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('semester');
            $this->hasColumn('id', 'integer', 4, ['primary' => 'true', 'autoincrement' => 'true']);
            $this->hasColumn('name', 'string', 255, ['notnull' => true]);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasMany('Course as Courses', ['local' => 'id', 'foreign' => 'semester_id']);
        }
    }

    class Weekday extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('weekday');
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('name', 'string', 9, ['notnull' => true, 'unique' => true]);
        }

        public function setUp(): void
        {
            // need to make the many-many bidirectional in order for the lazy-loading test to work.
            // lazy-loading the weekdays ($course['Weekdays']) doesnt work when the relation is
            // set up unidirectional. this is true for all many-many relations.
            $this->hasMany(
                'Course as courses',
                ['refClass' => 'CourseWeekday', 'local' => 'weekday_id', 'foreign' => 'course_id']
            );
        }
    }

    class Course extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('course');
            $this->hasColumn('id', 'integer', 4, ['primary' => 'true', 'autoincrement' => 'true']);
            $this->hasColumn('semester_id', 'integer', 4, ['notnull' => true]);
            $this->hasColumn('name', 'string', 255, ['notnull' => true]);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne(
                'Semester',
                ['local' => 'semester_id',
                                    'foreign'   => 'id',
                'onDelete'  => 'CASCADE']
            );
            $this->hasMany(
                'Weekday as Weekdays',
                ['refClass' => 'CourseWeekday', 'local' => 'course_id', 'foreign' => 'weekday_id']
            );
        }
    }

    class CourseWeekday extends \Doctrine1\Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('course_weekday');
            // Poor form to have an id on a join table, but that's what we were doing
            $this->hasColumn('id', 'integer', 4, ['primary' => true, 'autoincrement' => true]);
            $this->hasColumn('course_id', 'integer', 4, ['notnull' => true]);
            $this->hasColumn('weekday_id', 'integer', 4, ['notnull' => true]);
        }

        public function setUp(): void
        {
            parent::setUp();
            $this->hasOne('Course', ['local' => 'course_id', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
            $this->hasOne('Weekday', ['local' => 'weekday_id', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
        }
    }
}
