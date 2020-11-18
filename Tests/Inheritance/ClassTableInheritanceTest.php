<?php
namespace Tests\Inheritance {
    use Tests\DoctrineUnitTestCase;

    class ClassTableInheritanceTest extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
        }
        public static function prepareData(): void
        {
        }

        public function testClassTableInheritanceIsTheDefaultInheritanceType()
        {
            $class = new \CTITest();

            $table = $class->getTable();

            $this->assertEquals($table->getOption('joinedParents'), ['CTITestParent2', 'CTITestParent3']);
        }

        public function testExportGeneratesAllInheritedTables()
        {
            $sql = static::$conn->export->exportClassesSql(['CTITest', 'CTITestOneToManyRelated', 'NoIdTestParent', 'NoIdTestChild']);

            $this->assertEquals($sql[0], 'CREATE TABLE no_id_test_parent (myid INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
            $this->assertEquals($sql[1], 'CREATE TABLE no_id_test_child (myid INTEGER, child_column TEXT, PRIMARY KEY(myid))');
            $this->assertEquals($sql[2], 'CREATE TABLE c_t_i_test_parent4 (id INTEGER, age INTEGER, PRIMARY KEY(id))');
            $this->assertEquals($sql[3], 'CREATE TABLE c_t_i_test_parent3 (id INTEGER, added INTEGER, PRIMARY KEY(id))');
            $this->assertEquals($sql[4], 'CREATE TABLE c_t_i_test_parent2 (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(200), verified INTEGER)');

            foreach ($sql as $query) {
                static::$conn->exec($query);
            }
        }

        public function testInheritedPropertiesGetOwnerFlags()
        {
            $class = new \CTITest();

            $table = $class->getTable();

            $columns = $table->getColumns();

            $this->assertEquals($columns['verified']['owner'], 'CTITestParent2');
            $this->assertEquals($columns['name']['owner'], 'CTITestParent2');
            $this->assertEquals($columns['added']['owner'], 'CTITestParent3');
        }

        public function testNewlyCreatedRecordsHaveInheritedPropertiesInitialized()
        {
            $profiler = new \Doctrine_Connection_Profiler();

            static::$conn->addListener($profiler);

            $record = new \CTITest();

            $this->assertEquals(
                $record->toArray(),
                ['id'      => null,
                                                    'age'      => null,
                                                    'name'     => null,
                                                    'verified' => null,
                'added'    => null]
            );

            $record->age      = 13;
            $record->name     = 'Jack Daniels';
            $record->verified = true;
            $record->added    = time();
            $record->save();

            // pop the commit event
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent4 (age, id) VALUES (?, ?)');
            // pop the prepare event
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent3 (added, id) VALUES (?, ?)');
            // pop the prepare event
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent2 (name, verified) VALUES (?, ?)');
            static::$conn->addListener(new \Doctrine_EventListener());
        }

        public function testParentalJoinsAreAddedAutomaticallyWithDql()
        {
            $q = new \Doctrine_Query();
            $q->from('CTITest c')->where('c.id = 1');

            $this->assertEquals($q->getSqlQuery(), 'SELECT c.id AS c__id, c3.added AS c__added, c2.name AS c__name, c2.verified AS c__verified, c.age AS c__age FROM c_t_i_test_parent4 c LEFT JOIN c_t_i_test_parent2 c2 ON c.id = c2.id LEFT JOIN c_t_i_test_parent3 c3 ON c.id = c3.id WHERE (c.id = 1)');

            $record = $q->fetchOne();

            $this->assertEquals($record->id, 1);
            $this->assertEquals($record->name, 'Jack Daniels');
            $this->assertEquals($record->verified, true);
            $this->assertTrue(isset($record->added));
            $this->assertEquals($record->age, 13);
        }

        public function testReferenfingParentColumnsUsesProperAliases()
        {
            $q = new \Doctrine_Query();
            $q->from('CTITest c')->where("c.name = 'Jack'");

            $this->assertEquals($q->getSqlQuery(), "SELECT c.id AS c__id, c3.added AS c__added, c2.name AS c__name, c2.verified AS c__verified, c.age AS c__age FROM c_t_i_test_parent4 c LEFT JOIN c_t_i_test_parent2 c2 ON c.id = c2.id LEFT JOIN c_t_i_test_parent3 c3 ON c.id = c3.id WHERE (c2.name = 'Jack')");

            $q = new \Doctrine_Query();
            $q->from('CTITest c')->where("name = 'Jack'");

            $this->assertEquals($q->getSqlQuery(), "SELECT c.id AS c__id, c3.added AS c__added, c2.name AS c__name, c2.verified AS c__verified, c.age AS c__age FROM c_t_i_test_parent4 c LEFT JOIN c_t_i_test_parent2 c2 ON c.id = c2.id LEFT JOIN c_t_i_test_parent3 c3 ON c.id = c3.id WHERE (c2.name = 'Jack')");
        }

        public function testFetchingCtiRecordsSupportsLimitSubqueryAlgorithm()
        {
            $record         = new \CTITestOneToManyRelated;
            $record->name   = 'Someone';
            $record->cti_id = 1;
            $record->save();

            static::$conn->clear();

            $q = new \Doctrine_Query();
            $q->from('CTITestOneToManyRelated c')->leftJoin('c.CTITest c2')->where('c.id = 1')->limit(1);

            $record = $q->fetchOne();

            $this->assertEquals($record->name, 'Someone');
            $this->assertEquals($record->cti_id, 1);

            $cti = $record->CTITest[0];

            $this->assertEquals($cti->id, 1);
            $this->assertEquals($cti->name, 'Jack Daniels');
            $this->assertEquals($cti->verified, true);
            $this->assertTrue(isset($cti->added));
            $this->assertEquals($cti->age, 13);
        }

        public function testUpdatingCtiRecordsUpdatesAllParentTables()
        {
            static::$conn->clear();

            $profiler = new \Doctrine_Connection_Profiler();
            static::$conn->addListener($profiler);

            $record = static::$conn->getTable('CTITest')->find(1);

            $record->age      = 11;
            $record->name     = 'Jack';
            $record->verified = false;
            $record->added    = 0;

            $record->save();

            // pop the commit event
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'UPDATE c_t_i_test_parent4 SET age = ? WHERE id = ?');
            // pop the prepare event
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'UPDATE c_t_i_test_parent3 SET added = ? WHERE id = ?');
            // pop the prepare event
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'UPDATE c_t_i_test_parent2 SET name = ?, verified = ? WHERE id = ?');
            static::$conn->addListener(new \Doctrine_EventListener());
        }

        public function testUpdateOperationIsPersistent()
        {
            static::$conn->clear();

            $record = static::$conn->getTable('CTITest')->find(1);

            $this->assertEquals($record->id, 1);
            $this->assertEquals($record->name, 'Jack');
            $this->assertEquals($record->verified, false);
            $this->assertEquals($record->added, 0);
            $this->assertEquals($record->age, 11);
        }

        public function testValidationSkipsOwnerOption()
        {
            static::$conn->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);
            $record = static::$conn->getTable('CTITest')->find(1);
            $record->name = 'winston';
            $this->assertTrue($record->isValid());

            static::$conn->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_NONE);
        }

        public function testDeleteIssuesQueriesOnAllJoinedTables()
        {
            static::$conn->clear();

            $profiler = new \Doctrine_Connection_Profiler();
            static::$conn->addListener($profiler);

            $record = static::$conn->getTable('CTITest')->find(1);

            $record->delete();

            // pop the commit event

            // pop the prepare event
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'DELETE FROM c_t_i_test_parent2 WHERE id = ?');
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'DELETE FROM c_t_i_test_parent3 WHERE id = ?');
            $profiler->pop();
            $this->assertEquals($profiler->pop()->getQuery(), 'DELETE FROM c_t_i_test_parent4 WHERE id = ?');
            static::$conn->addListener(new \Doctrine_EventListener());
        }

        public function testNoIdCti()
        {
            $NoIdTestChild               = new \NoIdTestChild();
            $NoIdTestChild->name         = 'test';
            $NoIdTestChild->child_column = 'test';
            $NoIdTestChild->save();

            $NoIdTestChild = \Doctrine_Core::getTable('NoIdTestChild')->find(1);
            $this->assertEquals($NoIdTestChild->myid, 1);
            $this->assertEquals($NoIdTestChild->name, 'test');
            $this->assertEquals($NoIdTestChild->child_column, 'test');
        }
    }
}

namespace {
    abstract class CTIAbstractBase extends Doctrine_Record
    {
    }

    class CTITestParent1 extends CTIAbstractBase
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string', 200);
        }
    }
    class CTITestParent2 extends CTITestParent1
    {
        public function setTableDefinition()
        {
            parent::setTableDefinition();

            $this->hasColumn('verified', 'boolean', 1);
        }
    }
    class CTITestParent3 extends CTITestParent2
    {
        public function setTableDefinition()
        {
            $this->hasColumn('added', 'integer');
        }
    }
    class CTITestParent4 extends CTITestParent3
    {
        public function setTableDefinition()
        {
            $this->hasColumn('age', 'integer', 4);
        }
    }
    class CTITest extends CTITestParent4
    {
    }

    class CTITestOneToManyRelated extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('name', 'string');
            $this->hasColumn('cti_id', 'integer');
        }

        public function setUp()
        {
            $this->hasMany('CTITest', ['local' => 'cti_id', 'foreign' => 'id']);
        }
    }

    class NoIdTestParent extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('myid', 'integer', null, ['autoincrement' => true, 'primary' => true]);
            $this->hasColumn('name', 'string');
        }
    }

    class NoIdTestChild extends NoIdTestParent
    {
        public function setTableDefinition()
        {
            $this->hasColumn('child_column', 'string');
        }
    }
}
