<?php

namespace Doctrine1\Serializer;

use Doctrine1\Column;
use Doctrine1\Table;

class Boolean implements SerializerInterface
{
    protected function checkCompatibility(mixed $value, Column\Type $type): void
    {
        if ($type !== Column\Type::Boolean || $value === null || !is_scalar($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, Column $column, Table $table): mixed
    {
        $this->checkCompatibility($value, $column->type);
        $value = $table->getConnection()->convertBooleans($value);
        assert(!is_array($value)); // checkCompatibility requires a scalar type and convertBooleans only returns an array if the input is an array
        return $value;
    }

    public function areEquivalent(mixed $a, mixed $b, Column $column, Table $table): bool
    {
        $this->checkCompatibility($a, $column->type);
        if (is_numeric($a)) {
            $a = (int) $a;
        }
        if (is_numeric($b)) {
            $b = (int) $b;
        }
        $a = (bool) $a;
        $b = (bool) $b;
        return $a === $b;
    }
}
