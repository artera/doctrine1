<?php

namespace Doctrine1\Validator;

use Laminas\Validator\ValidatorInterface;

class ErrorStack extends \Doctrine1\Access implements \Countable, \IteratorAggregate
{
    /**
     * The errors of the error stack.
     *
     * @phpstan-var array<string, string[]>
     */
    protected array $errors = [];

    /**
     * Array of validators that failed
     *
     * @phpstan-var array<string, ValidatorInterface[]>
     */
    protected array $validators = [];

    /**
     * Get model class name for the error stack
     */
    protected string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Adds an error to the stack.
     *
     * @param string $invalidFieldName
     */
    public function add($invalidFieldName, ValidatorInterface|string $error = 'general'): void
    {
        if (is_string($error)) {
            $this->errors[$invalidFieldName][] = $error;
            return;
        }

        $this->validators[$invalidFieldName][] = $error;
        $this->errors[$invalidFieldName] = array_merge(
            $this->errors[$invalidFieldName] ?? [],
            $error->getMessages(),
        );
    }

    /**
     * Removes all existing errors for the specified field from the stack.
     *
     * @param  scalar|null $fieldName
     * @return void
     */
    public function remove($fieldName)
    {
        unset($this->errors[$fieldName]);
        unset($this->validators[$fieldName]);
    }

    /**
     * Get errors for field
     *
     * @param string $fieldName
     * @phpstan-return string[]
     */
    public function get($fieldName)
    {
        return $this->errors[$fieldName] ?? [];
    }

    /**
     * Check if a field has an error
     *
     * @param  scalar|null $fieldName
     * @return boolean
     */
    public function contains($fieldName): bool
    {
        return array_key_exists($fieldName, $this->errors);
    }

    /**
     * Removes all errors from the stack.
     */
    public function clear(): void
    {
        $this->errors     = [];
        $this->validators = [];
    }

    /**
     * @phpstan-return \ArrayIterator<string, string[]>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->errors);
    }

    /**
     * @phpstan-return array<string, string[]>
     */
    public function toArray(): array
    {
        return $this->errors;
    }

    /**
     * Count the number of errors
     */
    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * Get the classname where the errors occured
     */
    public function getClassname(): string
    {
        return $this->className;
    }

    /**
     * Get array of failed validators
     *
     * @phpstan-return array<string, ValidatorInterface[]>
     */
    public function getValidators(): array
    {
        return $this->validators;
    }
}
