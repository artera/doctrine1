<?php

namespace Doctrine1\Deserializer;

use Doctrine1\Column;
use Doctrine1\Table;

class Enum implements DeserializerInterface
{
    public function deserialize(mixed $value, Column $column, Table $table): mixed
    {
        if ($column->type !== Column\Type::Enum) {
            throw new Exception\Incompatible();
        }

        $enumClass = $column->getEnumClass();

        if ($enumClass === null || !(is_int($value) || is_string($value))) {
            throw new Exception\Incompatible();
        }

        return $enumClass::from($value);
    }
}
