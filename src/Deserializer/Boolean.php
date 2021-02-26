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

    public function deserialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type'], $table);
        return (bool) $value;
    }
}
