<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class DateTime implements SerializerInterface
{
    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        if (!$value instanceof DateTime) {
            throw new Exception\Incompatible();
        }
        return $value->format('Y-m-d H:i:s');
    }
}
