<?php

namespace Doctrine1\Import\Definition;

use Doctrine1\Column;
use Doctrine1\Record;

class Table
{
    public string $topLevelClassName;

    /**
     * @phpstan-param list<Column> $columns
     * @phpstan-param list<Relation> $relations
     * @phpstan-param class-string<Record>|null $extends
     */
    public function __construct(
        public readonly string $name,
        public readonly string $className,
        public readonly array $columns,
        public readonly ?string $connection = null,
        public array $relations = [],
        public readonly array $indexes = [],
        public readonly array $attributes = [],
        public readonly array $checks = [],
        public ?string $extends = null,
        public ?string $tableExtends = null,
        public bool $isBaseClass = false,
        public bool $isMainClass = false,
        public readonly ?string $inheritanceType = null,
        public readonly array $subclasses = [],
    ) {
        $this->topLevelClassName = $className;
    }

    public function getColumn(string $name): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }
        return null;
    }

    public function getRelationByAlias(string $alias): ?Relation
    {
        foreach ($this->relations as $relation) {
            if ($relation->alias === $alias) {
                return $relation;
            }
        }
        return null;
    }
}
