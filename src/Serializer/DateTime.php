<?php

namespace Doctrine1\Serializer;

use Doctrine1\Column;
use Doctrine1\Table;

class DateTime implements SerializerInterface
{
    public function __construct(
        protected \DateTimeZone $timezone
    ) {
    }

    public function serialize(mixed $value, Column $column, Table $table): mixed
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new Exception\Incompatible();
        }
        if (!$value instanceof \DateTimeImmutable) {
            $value = \DateTimeImmutable::createFromInterface($value);
        }
        // only include the time part for other types of columns like timestamp/datetime/string
        // so that we compare only the date part for equivalence
        if ($column->type === Column\Type::Date) {
            return $value->setTimezone($this->timezone)->format('Y-m-d');
        }
        return $value->setTimezone($this->timezone)->format('Y-m-d H:i:s');
    }

    public function areEquivalent(mixed $a, mixed $b, Column $column, Table $table): bool
    {
        return $this->serialize($a, $column, $table) === $this->serialize($b, $column, $table);
    }
}
