<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class Gzip implements SerializerInterface
{
    protected function checkCompatibility(mixed $value, string $type): void
    {
        if ($type !== 'gzip' || !is_string($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type']);
        return gzcompress($value, 5);
    }

    public function areEquivalent(mixed $a, mixed $b, array $column, \Doctrine_Table $table): bool
    {
        $this->checkCompatibility($a, $column['type']);
        $this->checkCompatibility($b, $column['type']);
        return $a === $b;
    }
}
