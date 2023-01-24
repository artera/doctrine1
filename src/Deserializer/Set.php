<?php

namespace Doctrine1\Deserializer;

use BackedEnum;
use Doctrine1\Column;
use Doctrine1\Table;

class Set implements DeserializerInterface
{
    public function deserialize(mixed $value, Column $column, Table $table): mixed
    {
        if ($column->type !== Column\Type::Set) {
            throw new Exception\Incompatible();
        }

        $enumClass = $column->getEnumClass();

        if ($enumClass === null || !is_array($value)) {
            throw new Exception\Incompatible();
        }

        foreach ($value as $v) {
            if (!$v instanceof BackedEnum && !(is_int($v) || is_string($v))) {
                throw new Exception\Incompatible();
            }
        }

        foreach ($value as &$v) {
            if (!$v instanceof BackedEnum) {
                $v = $enumClass::from($v);
            }
        }

        return $value;
    }
}
