<?php

namespace Doctrine1\Export;

class Schema
{
    /**
     * buildSchema
     *
     * Build schema array that can be dumped to file
     *
     * @param  string  $directory    The directory of models to build the schema from
     * @param  array   $models       The array of model names to build the schema for
     * @return array
     */
    public function buildSchema($directory = null, $models = [])
    {
        if ($directory !== null) {
            $loadedModels = \Doctrine1\Core::filterInvalidModels(\Doctrine1\Core::loadModels($directory));
        } else {
            $loadedModels = \Doctrine1\Core::getLoadedModels();
        }

        $array = [];

        $parent = new \ReflectionClass('\Doctrine1\Record');

        $sql = [];
        $fks = [];

        // we iterate through the diff of previously declared classes
        // and currently declared classes
        foreach ($loadedModels as $className) {
            if (!empty($models) && !in_array($className, $models)) {
                continue;
            }

            $recordTable = \Doctrine1\Core::getTable($className);

            $data = $recordTable->getExportableFormat();

            $table               = [];
            $table['connection'] = $recordTable->getConnection()->getName();
            $remove              = ['ptype', 'ntype', 'alltypes'];
            // Fix explicit length in schema, concat it to type in this format: type(length)
            foreach ($data['columns'] as $name => &$column) {
                if (!is_array($column)) {
                    continue;
                }

                if (!empty($column['length'])) {
                    if (!empty($column['scale'])) {
                        $column['type'] .= '(' . $column['length'] . ', ' . $column['scale'] . ')';
                        unset($column['scale']);
                    } else {
                        $column['type'] .= '(' . $column['length'] . ')';
                    }
                    unset($column['length']);
                }

                // Strip out schema information which is not necessary to be dumped to the yaml schema file
                foreach ($remove as $value) {
                    /** @var string $value */
                    unset($column[$value]);
                }

                // If type is the only property of the column then lets abbreviate the syntax
                // columns: { name: string(255) }
                if (count($column) === 1 && isset($column['type'])) {
                    $column = $column['type'];
                }
            }
            $table['tableName'] = $data['tableName'];
            $table['columns']   = $data['columns'];

            $relations = $recordTable->getRelations();
            foreach ($relations as $key => $relation) {
                $relationData = $relation->toArray();

                $relationKey = $relationData['alias'];

                if (isset($relationData['refTable']) && $relationData['refTable']) {
                    $table['relations'][$relationKey]['refClass'] = $relationData['refTable']->getComponentName();
                }

                if (isset($relationData['class']) && $relationData['class'] && $relation['class'] != $relationKey) {
                    $table['relations'][$relationKey]['class'] = $relationData['class'];
                }

                $table['relations'][$relationKey]['local']   = $relationData['local'];
                $table['relations'][$relationKey]['foreign'] = $relationData['foreign'];

                if ($relationData['type'] === \Doctrine1\Relation::ONE) {
                    $table['relations'][$relationKey]['type'] = 'one';
                } elseif ($relationData['type'] === \Doctrine1\Relation::MANY) {
                    $table['relations'][$relationKey]['type'] = 'many';
                } else {
                    $table['relations'][$relationKey]['type'] = 'one';
                }
            }

            $array[$className] = $table;
        }

        return $array;
    }

    /**
     * exportSchema
     *
     * @param  string  $schema
     * @param  string  $format
     * @param  string  $directory
     * @param  array   $models
     */
    public function exportSchema($schema, $format = 'yml', $directory = null, $models = []): int|string|null
    {
        $array = $this->buildSchema($directory, $models);

        if (is_dir($schema)) {
            $schema = $schema . DIRECTORY_SEPARATOR . 'schema.' . $format;
        }

        return \Doctrine1\Parser::dump($array, $format, $schema);
    }
}
