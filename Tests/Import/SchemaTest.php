<?php
namespace Tests\Import;

use Tests\DoctrineUnitTestCase;

class SchemaTest extends DoctrineUnitTestCase
{
    public $buildSchema;
    public $schema;

    public function testYmlImport()
    {
        $path = __DIR__ . '/import_builder_test';

        $import = new \Doctrine_Import_Schema();
        $import->importSchema(dirname(__DIR__) . '/schema.yml', 'yml', $path);

        $this->assertTrue(file_exists($path . '/SchemaTestUser.php'));
        $this->assertTrue(file_exists($path . '/SchemaTestProfile.php'));

        $this->assertEquals(\Doctrine_Core::getTable('AliasTest')->getFieldName('test_col'), 'test_col_alias');

        \Doctrine_Lib::removeDirectories($path);
    }

    public function testBuildSchema()
    {
        $schema = new \Doctrine_Import_Schema();
        $array  = $schema->buildSchema(dirname(__DIR__) . '/schema.yml', 'yml');

        $model = $array['SchemaTestUser'];

        $this->assertTrue(array_key_exists('connection', $model));
        $this->assertTrue(array_key_exists('className', $model));
        $this->assertTrue(array_key_exists('tableName', $model));
        $this->assertTrue(array_key_exists('columns', $model) && is_array($model['columns']));
        $this->assertTrue(array_key_exists('relations', $model) && is_array($model['relations']));
        $this->assertTrue(array_key_exists('indexes', $model) && is_array($model['indexes']));
        $this->assertTrue(array_key_exists('attributes', $model) && is_array($model['attributes']));
        $this->assertTrue(array_key_exists('options', $model) && is_array($model['options']));
        $this->assertTrue(array_key_exists('package', $model));
        $this->assertTrue(array_key_exists('inheritance', $model) && is_array($model['inheritance']));
        $this->assertTrue(array_key_exists('detect_relations', $model) && is_bool($model['detect_relations']));
        $this->assertEquals($array['AliasTest']['columns']['test_col']['name'], 'test_col as test_col_alias');
    }

    public function testSchemaRelationshipCompletion()
    {
        $this->buildSchema = new \Doctrine_Import_Schema();
        $this->schema      = $this->buildSchema->buildSchema(dirname(__DIR__) . '/schema.yml', 'yml');

        foreach ($this->schema as $name => $properties) {
            foreach ($properties['relations'] as $alias => $relation) {
                $this->assertTrue($this->_verifyMultiDirectionalRelationship($name, $alias, $relation));
            }
        }
    }

    protected function _verifyMultiDirectionalRelationship($class, $relationAlias, $relation)
    {
        $foreignClass = $relation['class'];
        $foreignAlias = isset($relation['foreignAlias']) ? $relation['foreignAlias']:$class;

        $foreignClassRelations = $this->schema[$foreignClass]['relations'];

        // Check to see if the foreign class has the opposite end defined for the class/foreignAlias
        return isset($foreignClassRelations[$foreignAlias]);
    }
}
