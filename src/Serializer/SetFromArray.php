<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class SetFromArray implements SerializerInterface
{
    public function canSerialize(mixed $value, string $forDbType): bool
    {
        return $forDbType === 'set' && is_array($value);
    }

    /** @param array $value */
    public function serialize(mixed $value, Doctrine_Connection $connection): mixed
    {
        return implode(',', $value);
    }
}
