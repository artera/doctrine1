<?php

namespace Doctrine1\Deserializer;

class Boolean implements DeserializerInterface
{
    protected function checkCompatibility(mixed $value, string $type): void
    {
        if ($type !== 'boolean' || !is_scalar($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function deserialize(mixed $value, array $column, \Doctrine1\Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type']);
        return (bool) $value;
    }
}
