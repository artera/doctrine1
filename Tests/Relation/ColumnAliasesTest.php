<?php
namespace Tests\Relation {
    use Tests\DoctrineUnitTestCase;

    class ColumnAliasesTest extends DoctrineUnitTestCase
    {
        protected static array $tables = [
            'ColumnAliasTest2',
            'ColumnAliasTest3'
        ];

        public static function prepareData(): void
        {
        }

        public function testCompoundSave()
        {
            $c2                           = new \ColumnAliasTest2();
            $c2->alias1                   = 'foo';
            $c2->ColumnAliasTest3->alias1 = 'bar';
            $c2->save();

            $this->assertNotNull($c2->column_alias_test3_id);
        }


        public function testHasOneCompoundSave2()
        {
            $c3                           = new \ColumnAliasTest3();
            $c3->alias1                   = 'foo';
            $c3->ColumnAliasTest2->alias1 = 'bar';
            $c3->save();

            $this->assertNotNull($c3->id);
        }
    }
}

namespace {
    class ColumnAliasTest2 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('column_alias_test2_id as id', 'integer', null, ['autoincrement' => true, 'primary' => true]);
            $this->hasColumn('column1 as alias1', 'string', 200);
            $this->hasColumn('column2 as alias2', 'integer', 4);
            $this->hasColumn('relation_id as column_alias_test3_id', 'integer', 4, ['notnull' => true]);
        }

        public function setUp()
        {
            $this->hasOne('ColumnAliasTest3', ['local' => 'column_alias_test3_id', 'foreign' => 'id']);
        }
    }

    class ColumnAliasTest3 extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn('column_alias_test3_id as id', 'integer', null, ['autoincrement' => true, 'primary' => true]);
            $this->hasColumn('column1 as alias1', 'string', 200);
            $this->hasColumn('column2 as alias2', 'integer', 4);
        }

        public function setUp()
        {
            $this->hasOne('ColumnAliasTest2', ['local' => 'id', 'foreign' => 'column_alias_test3_id']);
        }
    }
}
