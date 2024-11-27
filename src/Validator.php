<?php

namespace Doctrine1;

use Doctrine1\Column\Type;
use Laminas\Validator\ValidatorInterface;

class Validator
{
    /**
     * This was undefined, added for static analysis and set to public so api isn't changed
     *
     * @var array
     */
    public $stack;

    /**
     * Get a validator instance for the passed $name
     *
     * @param string $name Name of the validator or the validator class name
     * @param mixed[] $options The validator options
     */
    public static function getValidator(string $name, array $options = []): ValidatorInterface
    {
        $validators = Manager::getInstance()->getValidators();

        if (isset($validators[$name])) {
            $validatorClass = $validators[$name];
            if (is_array($validatorClass)) {
                $options = array_merge($validatorClass['options'], $options);
                $validatorClass = $validatorClass['class'];
            }
        } elseif (class_exists($name) && is_subclass_of($name, ValidatorInterface::class)) {
            $validatorClass = $name;
        } else {
            throw new Exception("Validator named '$name' not available.");
        }

        return new $validatorClass($options);
    }

    /**
     * Validates a given record and saves possible errors in Validator::$stack
     */
    public function validateRecord(Record $record): void
    {
        /** @phpstan-var Table */
        $table = $record->getTable();

        // if record is transient all fields will be validated
        // if record is persistent only the modified fields will be validated
        $fields = $record->exists() ? $record->getModified() : $record->getData();
        foreach ($fields as $fieldName => $value) {
            $table->validateField($fieldName, $value, $record);
        }
    }

    /**
     * Validates the length of a field.
     *
     * @param  string          $value         Value to validate
     * @param  Type            $type          Type of field being validated
     * @param  string|null|int $maximumLength Maximum length allowed for the column
     */
    public static function validateLength(string $value, Type $type, string|null|int $maximumLength): bool
    {
        if ($maximumLength === null) {
            return true;
        }

        return match ($type) {
            Type::Timestamp, Type::Integer, Type::Enum, Type::Set => true,
            Type::Array, Type::Object => strlen(serialize($value)) <= $maximumLength,
            Type::Decimal, Type::Float => strlen(preg_replace('/[^0-9]/', '', $value) ?? $value) <= $maximumLength,
            Type::BLOB => strlen($value) <= $maximumLength,
            default => (new \Laminas\Validator\StringLength(['max' => (int) $maximumLength]))->isValid($value),
        };
    }

    /**
     * Whether or not errors exist on this validator
     */
    public function hasErrors(): bool
    {
        return count($this->stack) > 0;
    }

    /**
     * Validate the type of the passed variable
     *
     * @param  mixed  $var  Variable to validate
     * @param  Type|string $type Type of the variable expected
     */
    public static function isValidType($var, Type|string $type): bool
    {
        if (is_string($type)) {
            $type = Type::fromNative($type);
        }

        if ($var instanceof Expression) {
            return true;
        } elseif ($var === null) {
            return true;
        } elseif (is_object($var) && !$var instanceof \DateTimeInterface) {
            return $type === Type::Object;
        } elseif (is_array($var)) {
            return $type === Type::Array;
        }

        return match ($type) {
            Type::Float, Type::Decimal => (string) $var == strval(floatval($var)),
            Type::Integer => (string) $var == strval(round(floatval($var))),
            Type::String => is_string($var) || is_numeric($var),
            Type::BLOB => is_string($var) || is_resource($var),
            Type::Array => false,
            Type::Object => is_object($var),
            Type::Boolean => is_bool($var) || (is_numeric($var) && ($var == 0 || $var == 1)),
            Type::Timestamp => (new Validator\Timestamp())->isValid($var),
            Type::Time => (new Validator\Time())->isValid($var),
            Type::Date => (new Validator\Date())->isValid($var),
            Type::Enum => is_string($var) || is_int($var),
            Type::Set => is_string($var),
            default => true,
        };
    }
}
