<?php

namespace Doctrine1\Serializer;

use Doctrine1\Column;
use Doctrine1\Table;

class ArrayOrObject implements SerializerInterface
{
    protected function checkCompatibility(mixed $value, Column\Type $type): void
    {
        if (!in_array($type, [Column\Type::Array, Column\Type::Object]) || !(is_array($value) || is_object($value))) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, Column $column, Table $table): mixed
    {
        $this->checkCompatibility($value, $column->type);
        return serialize($value);
    }

    public function areEquivalent(mixed $a, mixed $b, Column $column, Table $table): bool
    {
        $this->checkCompatibility($a, $column->type);
        return $a === $b;
    }
}
