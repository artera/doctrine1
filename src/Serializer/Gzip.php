<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class Gzip implements SerializerInterface
{
    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        if ($column['type'] !== 'gzip' || !is_string($value)) {
            throw new Exception\Incompatible();
        }
        return gzcompress($value, 5);
    }
}
