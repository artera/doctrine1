<?php

namespace Doctrine1\Serializer;

use BackedEnum;
use Doctrine1\Column;
use Doctrine1\Table;

class SetFromArray implements SerializerInterface
{
    /**
     * @param mixed[] $set
     * @return (int|string)[]
     */
    private static function toStringArray(array $set): array
    {
        return array_map(fn ($v) => $v instanceof BackedEnum ? $v->value : $v, $set);
    }

    /**
     * @phpstan-assert mixed[] $value
     */
    protected function checkCompatibility(mixed $value, Column\Type $type): void
    {
        if ($type !== Column\Type::Set || !is_array($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function serialize(mixed $value, Column $column, Table $table): mixed
    {
        $this->checkCompatibility($value, $column->type);
        return implode(',', array_unique(self::toStringArray($value)));
    }

    public function areEquivalent(mixed $a, mixed $b, Column $column, Table $table): bool
    {
        $this->checkCompatibility($a, $column->type);
        $this->checkCompatibility($b, $column->type);
        $a = self::toStringArray($a);
        $b = self::toStringArray($b);
        return array_diff($a, $b) === array_diff($b, $a);
    }
}
