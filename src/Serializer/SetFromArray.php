<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class SetFromArray implements SerializerInterface
{
    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        if ($column['type'] !== 'set' || !is_array($value)) {
            throw new Exception\Incompatible();
        }
        return implode(',', $value);
    }
}
