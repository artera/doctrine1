<?php
namespace Tests\Core\Table {
    use Tests\DoctrineUnitTestCase;

    class RemoveColumnTest extends DoctrineUnitTestCase
    {
        public static function prepareTables(): void
        {
            static::$tables[] = 'RemoveColumnTest';
            parent::prepareTables();
        }

        protected function verifyNrColumnsAndFields($table, $nrExpected)
        {
            $nrColumns = $table->getColumnCount();

            $this->assertEquals($nrColumns, $nrExpected);
            $this->assertEquals($nrColumns, count($table->getColumns()));

            $this->assertEquals($nrColumns, count($table->getFieldNames()));
            foreach ($table->getFieldNames() as $field) {
                $this->assertTrue($table->hasField($field));
            }

            // the following are trivial because both getColumnNames and
            // hasColumn use \Table::_columns instead of \Table::_fieldNames
            $this->assertEquals($nrColumns, count($table->getColumnNames()));
            foreach ($table->getColumnNames() as $column) {
                $this->assertTrue($table->hasColumn($column));
            }
        }

        public function testAfterDefinition()
        {
            $table = static::$connection->getTable('RemoveColumnTest');
            $this->verifyNrColumnsAndFields($table, 4);
        }

        public function testAfterRemoveColumn()
        {
            $table = static::$connection->getTable('RemoveColumnTest');
            $table->removeColumn('bb');
            $table->removeColumn('CC');
            $this->verifyNrColumnsAndFields($table, 2);
        }
    }
}

namespace {
    class RemoveColumnTest extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->hasColumn('AA', 'integer', null, ['primary' => true]);
            $this->hasColumn('bb', 'integer');
            $this->hasColumn('CC', 'string', 10);
            $this->hasColumn('dd', 'string', 10);
        }
    }
}
