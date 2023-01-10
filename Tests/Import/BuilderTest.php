<?php

namespace Tests\Import;

use Tests\DoctrineUnitTestCase;

class BuilderTest extends DoctrineUnitTestCase
{
    public function testInheritanceGeneration()
    {
        $path = __DIR__ . '/import_builder_test';

        $import = new \Doctrine1\Import\Schema();
        $import->setOption('generateTableClasses', true);
        $import->importSchema([dirname(__DIR__) . '/schema.yml'], 'yml', $path);

        $schemaTestInheritanceParent = new \ReflectionClass(\SchemaTestInheritanceParent::class);
        $schemaTestInheritanceChild1 = new \ReflectionClass(\SchemaTestInheritanceChild1::class);
        $schemaTestInheritanceChild2 = new \ReflectionClass(\SchemaTestInheritanceChild2::class);

        $schemaTestInheritanceParentTable = new \ReflectionClass(\SchemaTestInheritanceParentTable::class);
        $schemaTestInheritanceChild1Table = new \ReflectionClass(\SchemaTestInheritanceChild1Table::class);
        $schemaTestInheritanceChild2Table = new \ReflectionClass(\SchemaTestInheritanceChild2Table::class);

        $this->assertTrue($schemaTestInheritanceParent->isSubClassOf(\Doctrine1\Record::class));
        $this->assertTrue($schemaTestInheritanceParent->isSubClassOf(\BaseSchemaTestInheritanceParent::class));
        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf(\BaseSchemaTestInheritanceChild1::class));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf(\BaseSchemaTestInheritanceChild2::class));

        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf(\SchemaTestInheritanceParent::class));
        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf(\BaseSchemaTestInheritanceParent::class));

        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf(\SchemaTestInheritanceParent::class));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf(\BaseSchemaTestInheritanceParent::class));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf(\SchemaTestInheritanceChild1::class));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf(\BaseSchemaTestInheritanceChild1::class));

        $this->assertTrue($schemaTestInheritanceParentTable->isSubClassOf(\Doctrine1\Table::class));
        $this->assertTrue($schemaTestInheritanceChild1Table->isSubClassOf(\SchemaTestInheritanceParentTable::class));

        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf(\SchemaTestInheritanceParentTable::class));
        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf(\SchemaTestInheritanceChild1Table::class));

        // Simple Inheritance
        $schemaTestSimpleInheritanceParent = new \ReflectionClass(\SchemaTestSimpleInheritanceParent::class);
        $schemaTestSimpleInheritanceChild  = new \ReflectionClass(\SchemaTestSimpleInheritanceChild::class);

        $this->assertTrue($schemaTestSimpleInheritanceParent->hasMethod('setTableDefinition'));
        $this->assertTrue($schemaTestSimpleInheritanceChild->isSubClassOf(\SchemaTestSimpleInheritanceParent::class));

        // Concrete Inheritance
        $schemaTestConcreteInheritanceParent = new \ReflectionClass(\SchemaTestConcreteInheritanceParent::class);
        $schemaTestConcreteInheritanceChild  = new \ReflectionClass(\SchemaTestConcreteInheritanceChild::class);

        // Column Aggregation Inheritance
        $schemaTestColumnAggregationInheritanceParent = new \ReflectionClass(\SchemaTestColumnAggregationInheritanceParent::class);
        $schemaTestColumnAggregationInheritanceChild  = new \ReflectionClass(\SchemaTestColumnAggregationInheritanceChild::class);

        $sql = \Doctrine1\Core::generateSqlFromArray([\SchemaTestSimpleInheritanceParent::class, \SchemaTestSimpleInheritanceChild::class]);
        $this->assertEquals(count($sql), 1);
        $this->assertEquals($sql[0], 'CREATE TABLE schema_test_simple_inheritance_parent (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), description VARCHAR(255))');

        $sql = \Doctrine1\Core::generateSqlFromArray([\SchemaTestConcreteInheritanceParent::class, \SchemaTestConcreteInheritanceChild::class]);
        $this->assertEquals(count($sql), 2);
        $this->assertEquals($sql[0], 'CREATE TABLE schema_test_concrete_inheritance_parent (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255))');
        $this->assertEquals($sql[1], 'CREATE TABLE schema_test_concrete_inheritance_child (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), title VARCHAR(255), description VARCHAR(255))');

        $sql = \Doctrine1\Core::generateSqlFromArray([\SchemaTestColumnAggregationInheritanceParent::class, \SchemaTestColumnAggregationInheritanceChild::class]);
        $this->assertEquals(count($sql), 2);
        $this->assertEquals($sql[0], 'CREATE TABLE schema_test_column_aggregation_inheritance_parent (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), type VARCHAR(255), title VARCHAR(255), description VARCHAR(255))');
        $this->assertEquals($sql[1], 'CREATE INDEX schema_test_column_aggregation_inheritance_parent_type_idx ON schema_test_column_aggregation_inheritance_parent (type)');

        \Doctrine1\Lib::removeDirectories($path);
    }

    public function testBaseTableClass()
    {
        $builder = new \Doctrine1\Import\Builder();
        $builder->setOption('baseTableClassName', \MyBaseTable::class);
        $class = $builder->buildTableClassDefinition(\MyTestTable::class, new \Doctrine1\Import\Definition\Table(
            \MyTest::class,
            \MyTest::class,
            [],
        ));
        $this->assertNotFalse(strpos($class, 'class MyTestTable extends MyBaseTable'));
    }
}
