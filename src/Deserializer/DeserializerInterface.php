<?php

namespace Doctrine1\Deserializer;

use Doctrine1\Column;
use Doctrine1\Table;

interface DeserializerInterface
{
    public function deserialize(mixed $value, Column $column, Table $table): mixed;
}
