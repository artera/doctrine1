<?php

namespace Doctrine1\Deserializer;

class DateTimeImmutable implements DeserializerInterface
{
    public function __construct(
        protected \DateTimeZone $timezone,
        protected array $validTypes = ['date', 'datetime', 'timestamp'],
    ) {}

    protected function checkCompatibility(mixed $value, string $type): void
    {
        if (!in_array($type, $this->validTypes) || !is_scalar($value)) {
            throw new Exception\Incompatible();
        }
    }

    public function deserialize(mixed $value, array $column, \Doctrine_Table $table): mixed
    {
        $this->checkCompatibility($value, $column['type']);

        if ($value === null || (is_string($value) && substr($value, 0, 10) === '0000-00-00')) {
            if (empty($column['notnull'])) {
                return null;
            }

            if (!empty($column['default'])) {
                $value = $column['default'];
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
                return $date;
            }
        }

        return new \DateTimeImmutable($value);
    }
}
