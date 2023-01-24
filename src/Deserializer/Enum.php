<?php

namespace Doctrine1\Deserializer;

use Doctrine1\Column;
use Doctrine1\Table;

class Enum implements DeserializerInterface
{
    /** @phpstan-assert int|string $value */
    protected function checkCompatibility(mixed $value, Column\Type $type): void
    {
        if ($type !== Column\Type::Enum || !(is_int($value) || is_string($value))) {
            throw new Exception\Incompatible();
        }
    }

    public function deserialize(mixed $value, Column $column, Table $table): mixed
    {
        $enumClass = $column->getEnumClass();

        if ($enumClass === null || !(is_int($value) || is_string($value))) {
            throw new Exception\Incompatible();
        }

        return $enumClass::from($value);
    }
}
