<?php

namespace Doctrine1;

use Doctrine1\Column\Type;

class Column
{
    /** @phpstan-var non-empty-string */
    public readonly string $name;

    /** @phpstan-var non-empty-string */
    public readonly string $fieldName;

    /** @phpstan-var ?positive-int */
    public readonly ?int $length;

    /**
     * @phpstan-param non-empty-string $name
     * @phpstan-param ?positive-int $length
     * @phpstan-param int<0, 30> $scale
     * @phpstan-param ?class-string<Record> $owner
     * @phpstan-param array<string, bool|mixed[]> $validators
     */
    public function __construct(
        string $name,
        public readonly Type $type,
        ?int $length = null,
        public ?string $owner = null,
        public bool $primary = false,
        public mixed $default = null,
        bool $hasDefault = false,
        public bool $notnull = false,
        public array $values = [],
        public bool $autoincrement = false,
        public bool $unique = false,
        public bool $protected = false,
        public ?string $sequence = null,
        public bool $zerofill = false,
        public bool $unsigned = false,
        public int $scale = 0,
        public bool $fixed = false,
        public ?string $comment = null,
        public ?string $charset = null,
        public ?string $collation = null,
        public ?string $check = null,
        public ?int $min = null,
        public ?int $max = null,
        public mixed $extra = null,
        public bool $virtual = false,
        public array $meta = [],
        protected array $validators = [],
    ) {
        $name = trim($name);

        // extract column name & field name
        if (preg_match('/^(.+)\s+as\s+(.+)$/i', $name, $matches)) {
            $name = $matches[1];
            $fieldName = $matches[2];
        } else {
            $fieldName = $name;
        }

        if (empty($name)) {
            throw new Exception("Invalid column name \"$name\"");
        }

        if (empty($fieldName)) {
            throw new Exception("Invalid field name \"$fieldName\"");
        }

        $this->name = $name;
        $this->fieldName = $fieldName;

        $this->length = $length ?? match ($type) {
            Type::Integer => 8,
            Type::Decimal => 18,
            Type::Boolean => 1,
            Type::Date => 10, // YYYY-MM-DD ISO 8601
            Type::Time => 14, // HH:NN:SS+00:00 ISO 8601
            Type::DateTime, Type::Timestamp => 25, // YYYY-MM-DDTHH:MM:SS+00:00 ISO 8601
            Type::Set => strlen(implode(',', $this->values)) ?: null,
            Type::Enum => max(...array_map('strlen', $this->values)) ?: null,
            default => null,
        };

        if ($default === null && !$hasDefault) {
            $this->default = None::instance();
        }
    }

    /**
     * @phpstan-param non-empty-string $name
     */
    public function rename(string $name): self
    {
        return new Column(
            $name,
            type: $this->type,
            length: $this->length,
            owner: $this->owner,
            primary: $this->primary,
            default: $this->hasDefault() ? $this->default : null,
            hasDefault: $this->hasDefault(),
            notnull: $this->notnull,
            values: $this->values,
            autoincrement: $this->autoincrement,
            unique: $this->unique,
            protected: $this->protected,
            sequence: $this->sequence,
            zerofill: $this->zerofill,
            unsigned: $this->unsigned,
            scale: $this->scale,
            fixed: $this->fixed,
            comment: $this->comment,
            charset: $this->charset,
            collation: $this->collation,
            check: $this->check,
            min: $this->min,
            max: $this->max,
            extra: $this->extra,
            virtual: $this->virtual,
            meta: $this->meta,
            validators: $this->validators,
        );
    }

    public function hasDefault(): bool
    {
        return !$this->default instanceof None;
    }

    /** @phpstan-return ?non-empty-string */
    public function alias(): ?string
    {
        return $this->name === $this->fieldName ? null : $this->fieldName;
    }

    /**
     * Gets the names of all validators
     *
     * @return array<string, mixed[]> names of validators
     */
    public function getValidators(): array
    {
        $validators = [];

        if ($this->notnull && !$this->autoincrement) {
            $validators['notnull'] = [];
        }

        if ($this->unsigned) {
            $validators['unsigned'] = [];
        }

        foreach ($this->validators as $name => $args) {
            // skip it if it's explicitly set to FALSE (i.e. notnull => false)
            if ($args === false) {
                continue;
            }
            $validators[$name] = $args === true ? [] : (array) $args;
        }

        return $validators;
    }

    /**
     * @phpstan-return array{
     *   type: non-empty-string,
     *   length: ?positive-int,
     *   notnull: bool,
     *   values: mixed[],
     *   default: mixed,
     *   autoincrement: bool,
     *   values: mixed[],
     *   owner: ?class-string<Record>,
     *   primary: bool,
     *   unique: bool,
     *   protected: bool,
     *   sequence: ?string,
     *   zerofill: bool,
     *   scale: int<0, 30>,
     *   fixed: bool,
     *   comment: ?string,
     *   alias: ?non-empty-string,
     *   extra: mixed,
     *   virtual: bool,
     *   meta: mixed[],
     *   validators: array<string, bool|mixed[]>,
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'length' => $this->length,
            'owner' => $this->owner,
            'alias' => $this->alias(),
            'primary' => $this->primary,
            'default' => $this->default,
            'notnull' => $this->notnull,
            'values' => $this->values,
            'autoincrement' => $this->autoincrement,
            'unique' => $this->unique,
            'protected' => $this->protected,
            'sequence' => $this->sequence,
            'zerofill' => $this->zerofill,
            'scale' => $this->scale,
            'fixed' => $this->fixed,
            'comment' => $this->comment,
            'extra' => $this->extra,
            'virtual' => $this->virtual,
            'meta' => $this->meta,
            'validators' => $this->validators,
        ];
    }
}
