<?php

namespace Doctrine1\Export;

use Doctrine1\Column;
use Doctrine1\Record;
use Doctrine1\Relation;

class Schema
{
    /**
     * Build schema array that can be dumped to file
     *
     * @param  string  $directory    The directory of models to build the schema from
     * @param  array   $models       The array of model names to build the schema for
     * @return array
     * @phpstan-return array<class-string<Record>, array{
     *   tableName: string,
     *   connection: string,
     *   relations?: array<string, array{
     *     refClass?: class-string<Record>,
     *     class?: class-string<Record>,
     *     local: string,
     *     foreign: string,
     *     type: 'one' | 'many',
     *   }>,
     *   columns: array<string, array{
     *     type: string,
     *   }>,
     * }>
     */
    public function buildSchema(?string $directory = null, array $models = []): array
    {
        if ($directory !== null) {
            $loadedModels = \Doctrine1\Core::filterInvalidModels(\Doctrine1\Core::loadModels($directory));
        } else {
            $loadedModels = \Doctrine1\Core::getLoadedModels();
        }

        $array = [];
        $parent = new \ReflectionClass(Record::class);

        // we iterate through the diff of previously declared classes
        // and currently declared classes
        foreach ($loadedModels as $className) {
            if (!empty($models) && !in_array($className, $models)) {
                continue;
            }

            $recordTable = \Doctrine1\Core::getTable($className);

            $data = $recordTable->getExportableFormat();

            $table = [
                'tableName' => $data['tableName'],
                'connection' => $recordTable->getConnection()->getName(),
                'relations' => [],
                'columns' => array_map(function (Column $column) {
                    $columnArr = $column->toArray();

                    // Fix explicit length in schema, concat it to type in this format: type(length)
                    if (!empty($column->length)) {
                        if ($column->scale) {
                            $columnArr['type'] .= "({$column->length}, {$column->scale})";
                        } else {
                            $columnArr['type'] .= "({$column->length})";
                        }
                        unset($columnArr['scale']);
                        unset($columnArr['length']);
                    }

                    return $columnArr;
                }, $data['columns']),
            ];

            $relations = $recordTable->getRelations();
            foreach ($relations as $key => $relation) {
                $relationData = $relation->toArray();

                $relationKey = $relation->getAlias();

                $relationInfo = [
                    'local' => $relation->getLocal(),
                    'foreign' => $relation->getForeign(),
                    'type' => match ($relation->getType()) {
                        Relation::MANY => 'many',
                        default => 'one',
                    },
                ];

                if (isset($relationData['refTable'])) {
                    $relationInfo['refClass'] = $relationData['refTable']->getComponentName();
                }

                if ($relation['class'] != $relationKey) {
                    $relationInfo['class'] = $relationData['class'];
                }

                $table['relations'][$relationKey] = $relationInfo;
            }

            $array[$className] = $table;
        }

        return $array;
    }

    public function exportSchema(string $schema, string $format = 'yml', ?string $directory = null, array $models = []): int|string|null
    {
        $array = $this->buildSchema($directory, $models);

        if (is_dir($schema)) {
            $schema = $schema . DIRECTORY_SEPARATOR . 'schema.' . $format;
        }

        return \Doctrine1\Parser::dump($array, $format, $schema);
    }
}
