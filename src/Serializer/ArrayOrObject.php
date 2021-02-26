<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class ArrayOrObject implements SerializerInterface
{
    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        if (!in_array($column['type'], ['array', 'object']) || !(is_array($value) || is_object($value))) {
            throw new Exception\Incompatible();
        }
        return serialize($value);
    }
}
