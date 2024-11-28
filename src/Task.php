<?php

namespace Doctrine1;

use InvalidArgumentException;

/**
 * Abstract class used for writing Doctrine Tasks
 */
abstract class Task
{
    /**
     * @var Cli|null
     */
    public $dispatcher = null;

    /**
     * treat as protected
     * @var string|null
     */
    public $taskName = null;

    /**
     * @var string|null
     */
    public $description = null;

    /**
     * @var array
     */
    public $arguments = [];

    /**
     * @var array
     */
    public $requiredArguments = [];

    /**
     * @var array
     */
    public $optionalArguments = [];

    /**
     * __construct
     *
     * Since this is an abstract classes that extend this must follow a patter of Task_{TASK_NAME}
     * This is what determines the task name for executing it.
     *
     * @param  Cli $dispatcher
     * @return void
     */
    public function __construct($dispatcher = null)
    {
        $this->dispatcher = $dispatcher;

        $taskName = $this->getTaskName();

        //Derive the task name only if it wasn't entered at design-time
        if ($taskName === null) {
            $taskName = self::deriveTaskName(get_class($this));
        }

        /*
         * All task names must be passed through Task::setTaskName() to make sure they're valid.  We're most
         * interested in validating manually-entered task names, which are as good as arguments.
         */
        $this->setTaskName($taskName);
    }

    /**
     * Returns the name of the task the specified class _would_ implement
     *
     * N.B. This method does not check if the specified class is actually a Doctrine Task
     *
     * This is public so we can easily test its reactions to fully-qualified class names, without having to add
     * PHP 5.3-specific test code
     *
     * @param  string $className
     * @return string
     */
    public static function deriveTaskName($className)
    {
        if (($pos = strrpos($className, '\\')) !== false) {
            $className = substr($className, $pos + 1);
        }
        return str_replace('_', '-', Inflector::tableize($className));
    }

    public function notify(?string $notification = null): ?string
    {
        if (is_object($this->dispatcher)) {
            $args = func_get_args();
            return call_user_func_array([$this->dispatcher, 'notify'], $args);
        } elseif ($notification !== null) {
            return $notification;
        } else {
            return null;
        }
    }

    /**
     * ask
     * @param mixed[] $args
     * @return string
     */
    public function ask(...$args)
    {
        call_user_func_array([$this, 'notify'], $args);
        return strtolower(trim(fgets(STDIN) ?: ''));
    }

    /**
     * execute
     *
     * Override with each task class
     *
     * @return   void
     * @abstract
     */
    abstract public function execute();

    /**
     * Validates that all required fields are present
     */
    public function validate(): bool
    {
        $requiredArguments = $this->getRequiredArguments();

        foreach ($requiredArguments as $arg) {
            if (!isset($this->arguments[$arg])) {
                return false;
            }
        }

        return true;
    }

    /**
     * addArgument
     *
     * @param  string $name
     * @param  string $value
     * @return void
     */
    public function addArgument($name, $value)
    {
        $this->arguments[$name] = $value;
    }

    /**
     * getArgument
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        if (isset($this->arguments[$name])) {
            return $this->arguments[$name];
        } else {
            return $default;
        }
    }

    /**
     * getArguments
     *
     * @return array $arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * setArguments
     *
     * @param  array $args
     * @return void
     */
    public function setArguments(array $args)
    {
        $this->arguments = $args;
    }

    /**
     * Returns TRUE if the specified task name is valid, or FALSE otherwise
     *
     * @param  string $taskName
     * @return bool
     */
    protected static function validateTaskName(string $taskName): bool
    {
        /*
         * This follows the _apparent_ naming convention.  The key thing is to prevent the use of characters that would
         * break a command string - we definitely can't allow spaces, for example.
         */
        return (bool) preg_match('/^[a-z0-9][a-z0-9\-]*$/', $taskName);
    }

    /**
     * Sets the name of the task, the name that's used to invoke it through a CLI
     *
     * @throws InvalidArgumentException If the task name is invalid
     */
    protected function setTaskName(string $taskName): void
    {
        if (!self::validateTaskName($taskName)) {
            throw new InvalidArgumentException(
                sprintf('The task name "%s", in %s, is invalid', $taskName, get_class($this))
            );
        }

        $this->taskName = $taskName;
    }

    /**
     * @phpstan-return string|null $taskName
     */
    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    /**
     * getDescription
     *
     * @return string|null $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * getRequiredArguments
     *
     * @return array $requiredArguments
     */
    public function getRequiredArguments()
    {
        return array_keys($this->requiredArguments);
    }

    /**
     * getOptionalArguments
     *
     * @return array $optionalArguments
     */
    public function getOptionalArguments()
    {
        return array_keys($this->optionalArguments);
    }

    /**
     * getRequiredArgumentsDescriptions
     *
     * @return array $requiredArgumentsDescriptions
     */
    public function getRequiredArgumentsDescriptions()
    {
        return $this->requiredArguments;
    }

    /**
     * getOptionalArgumentsDescriptions
     *
     * @return array $optionalArgumentsDescriptions
     */
    public function getOptionalArgumentsDescriptions()
    {
        return $this->optionalArguments;
    }
}
