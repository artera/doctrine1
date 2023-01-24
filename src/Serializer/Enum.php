<?php

namespace Doctrine1\Serializer;

use BackedEnum;
use Doctrine1\Column;
use Doctrine1\Table;

class Enum implements SerializerInterface
{
    /** @phpstan-assert BackedEnum $value */
    protected function checkCompatibility(mixed $value, Column\Type $type): void
    {
        if ($type !== Column\Type::Enum || !$value instanceof BackedEnum) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, Column $column, Table $table): mixed
    {
        $this->checkCompatibility($value, $column->type);
        return $value->value;
    }

    public function areEquivalent(mixed $a, mixed $b, Column $column, Table $table): bool
    {
        $this->checkCompatibility($a, $column->type);
        $this->checkCompatibility($b, $column->type);
        return $a === $b;
    }
}
