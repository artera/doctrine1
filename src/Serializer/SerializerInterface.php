<?php

namespace Doctrine1\Serializer;

interface SerializerInterface
{
    /**
     * @phpstan-param array{
     *   type: string,
     *   length: int,
     *   notnull?: bool,
     *   values?: array,
     *   default?: mixed,
     *   autoincrement?: bool,
     *   values?: mixed[],
     * } $column
     * @return scalar
     */
    public function serialize(mixed $value, array $column, \Doctrine1\Table $table): mixed;

    public function areEquivalent(mixed $a, mixed $b, array $column, \Doctrine1\Table $table): bool;
}
