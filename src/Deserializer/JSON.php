<?php

namespace Doctrine1\Deserializer;

use Doctrine_Connection;
use JsonSerializable;

class JSON implements DeserializerInterface
{
    public function __construct(
        protected bool $assoc = true,
    ) {}

    protected function checkCompatibility(mixed $value, string $type): void
    {
        if ($type !== 'json' || !is_string($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function deserialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type']);
        return json_decode($value, $this->assoc, flags: JSON_THROW_ON_ERROR);
    }
}
