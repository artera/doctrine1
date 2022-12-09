<?php

namespace Doctrine1\Serializer;

use Doctrine1\Column;
use Doctrine1\Table;

interface SerializerInterface
{
    /**
     * @return scalar|null
     */
    public function serialize(mixed $value, Column $column, Table $table): mixed;

    public function areEquivalent(mixed $a, mixed $b, Column $column, Table $table): bool;
}
