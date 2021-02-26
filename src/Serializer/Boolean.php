<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class Boolean implements SerializerInterface
{
    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        if ($column['type'] !== 'boolean') {
            throw new Exception\Incompatible();
        }
        return $table->getConnection()->convertBooleans($value);
    }
}
