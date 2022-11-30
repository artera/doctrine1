<?php

namespace Doctrine1\Validator;

/**
 * @phpstan-implements \IteratorAggregate<\Doctrine1\Record>
 */
class Exception extends \Doctrine1\Exception implements \Countable, \IteratorAggregate
{
    /**
     * @var array $invalid
     */
    private $invalid = [];

    /**
     * @param array $invalid
     */
    public function __construct(array $invalid)
    {
        $this->invalid = $invalid;
        parent::__construct($this->generateMessage());
    }

    /**
     * @return array
     */
    public function getInvalidRecords()
    {
        return $this->invalid;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->invalid);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->invalid);
    }

    /**
     * Generate a message with all classes that have exceptions
     *
     * @return string
     */
    private function generateMessage()
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
     *
     * @return void
     */
    public function inspect($function)
    {
        foreach ($this->invalid as $record) {
            call_user_func($function, $record->getErrorStack());
        }
    }
}
