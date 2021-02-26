<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

interface SerializerInterface
{
    /**
     * @phpstan-param array{type: string} $column
     * @return scalar
     */
    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed;

    public function areEquivalent(mixed $a, mixed $b, array $column, \Doctrine_Table $table): bool;
}
