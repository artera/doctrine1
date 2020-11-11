<?php
namespace Tests\Import;

use Tests\DoctrineUnitTestCase;

class BuilderTest extends DoctrineUnitTestCase
{
    public function testInheritanceGeneration()
    {
        $path = __DIR__ . '/import_builder_test';

        $import = new \Doctrine_Import_Schema();
        $import->setOption('generateTableClasses', true);
        $import->importSchema(dirname(__DIR__) . '/schema.yml', 'yml', $path);

        $models = \Doctrine_Core::loadModels($path, \Doctrine_Core::MODEL_LOADING_CONSERVATIVE);

        $schemaTestInheritanceParent = new \ReflectionClass('SchemaTestInheritanceParent');
        $schemaTestInheritanceChild1 = new \ReflectionClass('SchemaTestInheritanceChild1');
        $schemaTestInheritanceChild2 = new \ReflectionClass('SchemaTestInheritanceChild2');

        $schemaTestInheritanceParentTable = new \ReflectionClass('SchemaTestInheritanceParentTable');
        $schemaTestInheritanceChild1Table = new \ReflectionClass('SchemaTestInheritanceChild1Table');
        $schemaTestInheritanceChild2Table = new \ReflectionClass('SchemaTestInheritanceChild2Table');

        $this->assertTrue($schemaTestInheritanceParent->isSubClassOf('Doctrine_Record'));
        $this->assertTrue($schemaTestInheritanceParent->isSubClassOf('BaseSchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceParent->isSubClassOf('PackageSchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf('BaseSchemaTestInheritanceChild1'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('BaseSchemaTestInheritanceChild2'));

        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf('SchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf('BaseSchemaTestInheritanceParent'));

        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('SchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('BaseSchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('SchemaTestInheritanceChild1'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('BaseSchemaTestInheritanceChild1'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('PackageSchemaTestInheritanceParent'));

        $this->assertTrue($schemaTestInheritanceParentTable->isSubClassOf('Doctrine_Table'));
        $this->assertTrue($schemaTestInheritanceChild1Table->isSubClassOf('SchemaTestInheritanceParentTable'));
        $this->assertTrue($schemaTestInheritanceChild1Table->isSubClassOf('PackageSchemaTestInheritanceParentTable'));

        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf('SchemaTestInheritanceParentTable'));
        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf('PackageSchemaTestInheritanceParentTable'));
        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf('SchemaTestInheritanceChild1Table'));
        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf('PackageSchemaTestInheritanceChild1Table'));

        // Simple Inheritance
        $schemaTestSimpleInheritanceParent = new \ReflectionClass('SchemaTestSimpleInheritanceParent');
        $schemaTestSimpleInheritanceChild  = new \ReflectionClass('SchemaTestSimpleInheritanceChild');

        $this->assertTrue($schemaTestSimpleInheritanceParent->hasMethod('setTableDefinition'));
        $this->assertTrue($schemaTestSimpleInheritanceChild->isSubClassOf('SchemaTestSimpleInheritanceParent'));

        // Class Table Inheritance
        $schemaTestClassTableInheritanceParent = new \ReflectionClass('SchemaTestClassTableInheritanceParent');
        $schemaTestClassTableInheritanceChild  = new \ReflectionClass('SchemaTestClassTableInheritanceChild');

        // Concrete Inheritance
        $schemaTestConcreteInheritanceParent = new \ReflectionClass('SchemaTestConcreteInheritanceParent');
        $schemaTestConcreteInheritanceChild  = new \ReflectionClass('SchemaTestConcreteInheritanceChild');

        // Column Aggregation Inheritance
        $schemaTestColumnAggregationInheritanceParent = new \ReflectionClass('SchemaTestColumnAggregationInheritanceParent');
        $schemaTestColumnAggregationInheritanceChild  = new \ReflectionClass('SchemaTestColumnAggregationInheritanceChild');

        $sql = \Doctrine_Core::generateSqlFromArray(['SchemaTestSimpleInheritanceParent', 'SchemaTestSimpleInheritanceChild']);
        $this->assertEquals(count($sql), 1);
        $this->assertEquals($sql[0], 'CREATE TABLE schema_test_simple_inheritance_parent (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), description VARCHAR(255))');

        $sql = \Doctrine_Core::generateSqlFromArray(['SchemaTestClassTableInheritanceParent', 'SchemaTestClassTableInheritanceChild']);
        $this->assertEquals(count($sql), 2);
        $this->assertEquals($sql[0], 'CREATE TABLE schema_test_class_table_inheritance_parent (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255))');
        $this->assertEquals($sql[1], 'CREATE TABLE schema_test_class_table_inheritance_child (id INTEGER, title VARCHAR(255), description VARCHAR(255), PRIMARY KEY(id))');

        $sql = \Doctrine_Core::generateSqlFromArray(['SchemaTestConcreteInheritanceParent', 'SchemaTestConcreteInheritanceChild']);
        $this->assertEquals(count($sql), 2);
        $this->assertEquals($sql[0], 'CREATE TABLE schema_test_concrete_inheritance_parent (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255))');
        $this->assertEquals($sql[1], 'CREATE TABLE schema_test_concrete_inheritance_child (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), title VARCHAR(255), description VARCHAR(255))');

        $sql = \Doctrine_Core::generateSqlFromArray(['SchemaTestColumnAggregationInheritanceParent', 'SchemaTestColumnAggregationInheritanceChild']);
        $this->assertEquals(count($sql), 2);
        $this->assertEquals($sql[0], 'CREATE TABLE schema_test_column_aggregation_inheritance_parent (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), type VARCHAR(255), title VARCHAR(255), description VARCHAR(255))');
        $this->assertEquals($sql[1], 'CREATE INDEX schema_test_column_aggregation_inheritance_parent_type_idx ON schema_test_column_aggregation_inheritance_parent (type)');

        \Doctrine_Lib::removeDirectories($path);
    }

    public function testBaseTableClass()
    {
        $builder = new \Doctrine_Import_Builder();
        $builder->setOption('baseTableClassName', 'MyBaseTable');
        $class = $builder->buildTableClassDefinition('MyTestTable', ['className' => 'MyTest']);
        $this->assertNotFalse(strpos($class, 'class MyTestTable extends MyBaseTable'));
    }
}
