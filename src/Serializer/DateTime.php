<?php

namespace Doctrine1\Serializer;

use Doctrine_Connection;

class DateTime implements SerializerInterface
{
    public function __construct(
        protected \DateTimeZone $timezone
    ) {}

    public function serialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        if (!$value instanceof \DateTimeInterface) {
            throw new Exception\Incompatible();
        }
        // only include the time part for other types of columns like timestamp/datetime/string
        // so that we compare only the date part for equivalence
        if ($column['type'] === 'date') {
            return $value->setTimezone($this->timezone)->format('Y-m-d');
        }
        return $value->setTimezone($this->timezone)->format('Y-m-d H:i:s');
    }

    public function areEquivalent(mixed $a, mixed $b, array $column, \Doctrine_Table $table): bool
    {
        return $this->serialize($a, $column, $table) === $this->serialize($b, $column, $table);
    }
}
