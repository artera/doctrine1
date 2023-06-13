<?php

namespace Doctrine1\Deserializer;

use Doctrine1\Column;
use Doctrine1\Table;

class DateTimeImmutable implements DeserializerInterface
{
    public function __construct(
        protected \DateTimeZone $timezone,
        protected array $validTypes = [Column\Type::Date, Column\Type::DateTime, Column\Type::Timestamp],
    ) {
    }

    protected function checkCompatibility(mixed $value, Column\Type $type): void
    {
        if (!in_array($type, $this->validTypes, true) || !is_scalar($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function deserialize(mixed $value, Column $column, Table $table): mixed
    {
        $this->checkCompatibility($value, $column->type);

        if ($value === null || (is_string($value) && substr($value, 0, 10) === '0000-00-00')) {
            if (!$column->notnull) {
                return null;
            }

            if ($column->hasDefault()) {
                $value = $column->default;
            }
        }

        if (is_callable($value)) {
            $value = $value();
            if ($value instanceof \DateTimeImmutable) {
                return $value;
            }
        }

        if (is_int($value) || is_numeric($value)) {
            return (new \DateTimeImmutable())->setTimestamp((int) $value);
        }

        foreach ([
            \DateTimeImmutable::ATOM,
            \DateTimeInterface::RFC3339_EXTENDED,
            \DateTimeInterface::RFC3339,
            'Y-m-d H-i-s',
            'Y-m-d H:i:s',
            'Y-m-d H-i',
            'Y-m-d H:i',
            'Y-m-d',
        ] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false) {
                if (!str_contains($format, 'H')) {
                    $date = $date->setTime(0, 0);
                } elseif (!str_contains($format, 'i')) {
                    $date = $date->setTime((int) $date->format('H'), 0);
                } elseif (!str_contains($format, 's')) {
                    $date = $date->setTime((int) $date->format('H'), (int) $date->format('i'));
                }
                return $date;
            }
        }

        return new \DateTimeImmutable($value);
    }
}
