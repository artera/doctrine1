<?php

namespace Doctrine1\Serializer;

class ArrayOrObject implements SerializerInterface
{
    protected function checkCompatibility(mixed $value, string $type): void
    {
        if (!in_array($type, ['array', 'object']) || !(is_array($value) || is_object($value))) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type']);
        return serialize($value);
    }

    public function areEquivalent(mixed $a, mixed $b, array $column, \Doctrine_Table $table): bool
    {
        $this->checkCompatibility($a, $column['type']);
        return $a === $b;
    }
}
