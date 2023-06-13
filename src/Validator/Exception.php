<?php

namespace Doctrine1\Validator;

use Doctrine1\Record;

/**
 * @phpstan-implements \IteratorAggregate<Record>
 */
class Exception extends \Doctrine1\Exception implements \Countable, \IteratorAggregate
{
    /**
     * @param Record[] $invalid
     */
    public function __construct(private array $invalid, ?\Throwable $previous = null)
    {
        parent::__construct($this->generateMessage(), previous: $previous);
    }

    /**
     * @return Record[]
     */
    public function getInvalidRecords(): array
    {
        return $this->invalid;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->invalid);
    }

    public function count(): int
    {
        return count($this->invalid);
    }

    /**
     * Generate a message with all classes that have exceptions
     */
    private function generateMessage(): string
    {
        $message = '';
        foreach ($this->invalid as $record) {
            $message .= $record->getErrorStackAsString();
        }
        return $message;
    }

    /**
     * This method will apply the value of the $function variable as a user_func
     * to tall errorstack objects in the exception
     *
     * @param callable $function Either string with function name or array with object,
     *                           functionname. See call_user_func in php manual for more inforamtion
     */
    public function inspect($function): void
    {
        foreach ($this->invalid as $record) {
            call_user_func($function, $record->getErrorStack());
        }
    }
}
