<?php

namespace Doctrine1;

use BackedEnum;
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
     * @phpstan-param list<string>|class-string<BackedEnum> $values
     */
    public function __construct(
        string $name,
        public readonly Type $type,
        ?int $length = null,
        ?string $fieldName = null,
        public ?string $owner = null,
        public bool $primary = false,
        public mixed $default = null,
        bool $hasDefault = false,
        public bool $notnull = false,
        protected array|string $values = [],
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
        public array $validators = [],
    ) {
        $name = trim($name);

        // extract column name & field name
        if (preg_match('/^(.+)\s+as\s+(.+)$/i', $name, $matches)) {
            if ($fieldName !== null) {
                throw new Exception('Invalid $fieldName and $name with alias combination');
            }

            $name = $matches[1];
            $fieldName = $matches[2];
        } else {
            $fieldName ??= $name;
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
            Type::Set => strlen(implode(',', $this->stringValues())) ?: null,
            Type::Enum => max(...array_map('strlen', $this->stringValues())) ?: null,
            default => null,
        };

        if ($default === null && !$hasDefault) {
            $this->default = None::instance();
        }
    }

    /** @return string[] | BackedEnum[] */
    public function values(): array
    {
        if (is_array($this->values)) {
            return $this->values;
        }
        return $this->values::cases();
    }

    /** @return string[] */
    public function stringValues(): array
    {
        if (is_array($this->values)) {
            return $this->values;
        }

        $values = [];
        foreach ($this->values() as $case) {
            $values[] = is_string($case) ? $case : (string) $case->value;
        }
        return $values;
    }

    /**
     * @phpstan-return class-string<BackedEnum>
     */
    public function getEnumClass(): ?string
    {
        return is_string($this->values) ? $this->values : null;
    }

    /**
     * @phpstan-param array{
     *   name?: string,
     *   type?: Type,
     *   length?: ?int,
     *   fieldName?: ?string,
     *   owner?: ?string,
     *   primary?: bool,
     *   default?: mixed,
     *   hasDefault?: bool,
     *   notnull?: bool,
     *   values?: array|string,
     *   autoincrement?: bool,
     *   unique?: bool,
     *   protected?: bool,
     *   sequence?: ?string,
     *   zerofill?: bool,
     *   unsigned?: bool,
     *   scale?: int,
     *   fixed?: bool,
     *   comment?: ?string,
     *   charset?: ?string,
     *   collation?: ?string,
     *   check?: ?string,
     *   min?: ?int,
     *   max?: ?int,
     *   extra?: mixed,
     *   virtual?: bool,
     *   meta?: array,
     *   validators?: array,
     * } $changes
     * @return static
     */
    public function modify(array $changes): self
    {
        return new static(
            $changes['name'] ?? $this->name,
            type:          array_key_exists('type', $changes) ? $changes['type'] : $this->type,
            length:        array_key_exists('length', $changes) ? $changes['length'] : $this->length,
            fieldName:     array_key_exists('fieldName', $changes) ? $changes['fieldName'] : $this->fieldName,
            owner:         array_key_exists('owner', $changes) ? $changes['owner'] : $this->owner,
            primary:       array_key_exists('primary', $changes) ? $changes['primary'] : $this->primary,
            default:       array_key_exists('default', $changes) ? $changes['default'] : ($this->hasDefault() ? $this->default : null),
            hasDefault:    array_key_exists('hasDefault', $changes) ? $changes['hasDefault'] : $this->hasDefault(),
            notnull:       array_key_exists('notnull', $changes) ? $changes['notnull'] : $this->notnull,
            values:        array_key_exists('values', $changes) ? $changes['values'] : $this->values,
            autoincrement: array_key_exists('autoincrement', $changes) ? $changes['autoincrement'] : $this->autoincrement,
            unique:        array_key_exists('unique', $changes) ? $changes['unique'] : $this->unique,
            protected:     array_key_exists('protected', $changes) ? $changes['protected'] : $this->protected,
            sequence:      array_key_exists('sequence', $changes) ? $changes['sequence'] : $this->sequence,
            zerofill:      array_key_exists('zerofill', $changes) ? $changes['zerofill'] : $this->zerofill,
            unsigned:      array_key_exists('unsigned', $changes) ? $changes['unsigned'] : $this->unsigned,
            scale:         array_key_exists('scale', $changes) ? $changes['scale'] : $this->scale,
            fixed:         array_key_exists('fixed', $changes) ? $changes['fixed'] : $this->fixed,
            comment:       array_key_exists('comment', $changes) ? $changes['comment'] : $this->comment,
            charset:       array_key_exists('charset', $changes) ? $changes['charset'] : $this->charset,
            collation:     array_key_exists('collation', $changes) ? $changes['collation'] : $this->collation,
            check:         array_key_exists('check', $changes) ? $changes['check'] : $this->check,
            min:           array_key_exists('min', $changes) ? $changes['min'] : $this->min,
            max:           array_key_exists('max', $changes) ? $changes['max'] : $this->max,
            extra:         array_key_exists('extra', $changes) ? $changes['extra'] : $this->extra,
            virtual:       array_key_exists('virtual', $changes) ? $changes['virtual'] : $this->virtual,
            meta:          array_key_exists('meta', $changes) ? $changes['meta'] : $this->meta,
            validators:    array_key_exists('validators', $changes) ? $changes['validators'] : $this->validators,
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
            'values' => $this->values(),
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

    public function enumImplementation(Configurable $conf): EnumSetImplementation
    {
        $columnImplementation = match ($this->type) {
            Type::Set => $conf->getSetImplementation(),
            default => $conf->getEnumImplementation(),
        };

        $importAs = null;

        if (isset($this->meta['importAs'])) {
            $importAs = $this->meta['importAs'];

            if (!is_array($importAs)) {
                $importAs = [
                    'type' => $importAs,
                    'name' => null,
                ];
            }

            $columnImplementation = EnumSetImplementation::tryFrom($importAs['type']) ?? $columnImplementation;
        }

        return $columnImplementation;
    }

    public function enumClassName(Configurable $conf, string $namespace): ?string
    {
        $columnImplementation = match ($this->type) {
            Type::Enum => $conf->getEnumImplementation(),
            Type::Set => $conf->getSetImplementation(),
            default => null,
        };

        if ($columnImplementation === null) {
            return null;
        }

        $importAs = null;

        if (isset($this->meta['importAs'])) {
            $importAs = $this->meta['importAs'];

            if (!is_array($importAs)) {
                $importAs = [
                    'type' => $importAs,
                    'name' => null,
                ];
            }

            $columnImplementation = EnumSetImplementation::tryFrom($importAs['type']) ?? $columnImplementation;
        }

        if ($columnImplementation !== EnumSetImplementation::Enum) {
            return null;
        }

        if (!empty($importAs['name'])) {
            return trim($importAs['name'], '\\');
        } else {
            return Lib::namespaceConcat($namespace, Inflector::classify($this->name));
        }
    }
}
