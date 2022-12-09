<?php

namespace Doctrine1\Serializer;

use JsonSerializable;
use Doctrine1\Column;
use Doctrine1\Table;

class JSON implements SerializerInterface
{
    protected function checkCompatibility(mixed $value, Column\Type $type): void
    {
        if ($type !== Column\Type::JSON || !(is_scalar($value) || is_array($value) || $value instanceof JsonSerializable)) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, Column $column, Table $table): mixed
    {
        $this->checkCompatibility($value, $column->type);
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function areEquivalent(mixed $a, mixed $b, Column $column, Table $table): bool
    {
        $this->checkCompatibility($a, $column->type);
        $this->checkCompatibility($b, $column->type);
        return $a === $b;
    }
}
