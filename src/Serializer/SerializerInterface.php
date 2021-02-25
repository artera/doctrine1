<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

interface SerializerInterface
{
    public function canSerialize(mixed $value, string $forDbType): bool;

    /** @return scalar */
    public function serialize(mixed $value, Doctrine_Connection $connection): mixed;
}
