<?php

namespace Doctrine1\Column;

enum Type: string
{
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Float = 'float';
    case Double = 'double';
    case String = 'string';
    case Text = 'text';
    case Boolean = 'boolean';
    case Bit = 'bit';
    case JSON = 'json';
    case BLOB = 'blob';
    case Date = 'date';
    case DateTime = 'datetime';
    case Time = 'time';
    case Timestamp = 'timestamp';
    case Set = 'set';
    case Enum = 'enum';
    case Object = 'object';
    case Array = 'array';
    case Inet = 'inet';

    public function default(): string|bool|int|float
    {
        return match ($this) {
            self::Boolean   => false,
            self::Integer   => 0,
            self::Decimal   => 0.0,
            self::Float     => 0.0,
            self::Timestamp => '1970-01-01 00:00:00',
            self::Time      => '00:00:00',
            self::Date      => '1970-01-01',
            default         => '',
        };
    }

    public static function fromNative(string $type): self
    {
        return match ($type) {
            'char', 'varchar' => self::String,
            'varbit' => self::Bit,
            default => self::from($type),
        };
    }
}
