<?php

namespace Doctrine1\Deserializer;

interface DeserializerInterface
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
     */
    public function deserialize(mixed $value, array $column, \Doctrine_Table $table): mixed;
}
