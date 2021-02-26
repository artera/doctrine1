<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class SetFromArray implements SerializerInterface
{
    protected function checkCompatibility(mixed $value, string $type): void
    {
        if ($type !== 'set' || !is_array($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type']);
        return implode(',', array_unique($value));
    }

    public function areEquivalent(mixed $a, mixed $b, array $column, \Doctrine_Table $table): bool
    {
        $this->checkCompatibility($a, $column['type']);
        $this->checkCompatibility($b, $column['type']);
        return array_diff($a, $b) === array_diff($b, $a);
    }
}
