<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class Gzip implements SerializerInterface
{
    public function canSerialize(mixed $value, string $forDbType): bool
    {
        return $forDbType === 'gzip' && is_string($value);
    }

    /** @param string $value */
    public function serialize(mixed $value, Doctrine_Connection $connection): mixed
    {
        return gzcompress($value, 5);
    }
}
