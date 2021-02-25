<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class ArrayOrObject implements SerializerInterface
{
    public function canSerialize(mixed $value, string $forDbType): bool
    {
        return in_array($forDbType, ['array', 'object']) && (is_array($value) || is_object($value));
    }

    /** @param array|object $value */
    public function serialize(mixed $value, Doctrine_Connection $connection): mixed
    {
        return serialize($value);
    }
}
