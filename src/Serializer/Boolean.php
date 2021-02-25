<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class Boolean implements SerializerInterface
{
    public function canSerialize(mixed $value, string $forDbType): bool
    {
        return $forDbType === 'boolean';
    }

    /** @param array $value */
    public function serialize(mixed $value, Doctrine_Connection $connection): mixed
    {
        return $connection->convertBooleans($value);
    }
}
