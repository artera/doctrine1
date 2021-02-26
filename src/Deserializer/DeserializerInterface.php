<?php

namespace Doctrine1\Deserializer;

use Doctrine_Connection;

interface DeserializerInterface
{
    /**
     * @phpstan-param array{type: string} $column
     * @return scalar
     */
    public function deserialize(mixed $value, array $column, \Doctrine_Table $table): mixed;
}
