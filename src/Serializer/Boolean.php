<?php

namespace Doctrine1\Serializer;

class Boolean implements SerializerInterface
{
    protected function checkCompatibility(mixed $value, string $type): void
    {
        if ($type !== 'boolean' || $value === null || !is_scalar($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type']);
        $value = $table->getConnection()->convertBooleans($value);
        assert(!is_array($value)); // checkCompatibility requires a scalar type and convertBooleans only returns an array if the input is an array
        return $value;
    }

    public function areEquivalent(mixed $a, mixed $b, array $column, \Doctrine_Table $table): bool
    {
        $this->checkCompatibility($a, $column['type']);
        if (is_numeric($a)) {
            $a = (int) $a;
        }
        if (is_numeric($b)) {
            $b = (int) $b;
        }
        $a = (bool) $a;
        $b = (bool) $b;
        return $a === $b;
    }
}
