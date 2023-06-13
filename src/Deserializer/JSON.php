<?php

namespace Doctrine1\Deserializer;

use Doctrine1\Column;
use Doctrine1\Table;

class JSON implements DeserializerInterface
{
    public function __construct(
        protected bool $assoc = true,
    ) {
    }

    protected function checkCompatibility(mixed $value, Column\Type $type): void
    {
        if ($type !== Column\Type::JSON || !is_string($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function deserialize(mixed $value, Column $column, Table $table): mixed
    {
        $this->checkCompatibility($value, $column->type);
        try {
            return json_decode($value, $this->assoc, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            if (in_array($e->getCode(), [JSON_ERROR_SYNTAX, JSON_ERROR_STATE_MISMATCH, JSON_ERROR_UNSUPPORTED_TYPE])) {
                throw new Exception\Incompatible();
            }
            throw $e;
        }
    }
}
