<?php

namespace Doctrine1\Serializer;

use JsonSerializable;

class JSON implements SerializerInterface
{
    protected function checkCompatibility(mixed $value, string $type): void
    {
        if ($type !== 'json' || !(is_scalar($value) || is_array($value) || $value instanceof JsonSerializable)) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, array $column, \Doctrine1\Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type']);
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function areEquivalent(mixed $a, mixed $b, array $column, \Doctrine1\Table $table): bool
    {
        $this->checkCompatibility($a, $column['type']);
        $this->checkCompatibility($b, $column['type']);
        return $a === $b;
    }
}
