<?php

namespace Doctrine1;

use Throwable;

/**
 * Command line interface class
 *
 * Interface for easily executing Task classes from a command line interface
 */
class Cli
{
    public const TASK_BASE_CLASS = Task::class;

    protected ?string $scriptName = null;

    private array $config = [];

    private Cli\Formatter $formatter;

    /**
     * An array, keyed on class name, containing task instances
     *
     * @phpstan-var array<string, Task>
     */
    private array $registeredTask = [];

    private Task $taskInstance;

    public function __construct(array $config = [], ?Cli\Formatter $formatter = null)
    {
        $this->setConfig($config);
        $this->setFormatter($formatter ? $formatter : new Cli\AnsiColorFormatter());
        $this->includeAndRegisterTaskClasses();
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function setFormatter(Cli\Formatter $formatter): void
    {
        $this->formatter = $formatter;
    }

    public function getFormatter(): Cli\Formatter
    {
        return $this->formatter;
    }

    /**
     * Returns the specified value from the config, or the default value, if specified
     *
     * @throws \OutOfBoundsException If the element does not exist in the config
     */
    public function getConfigValue(string $name): mixed
    {
        if (!isset($this->config[$name])) {
            if (func_num_args() > 1) {
                return func_get_arg(1);
            }

            throw new \OutOfBoundsException("The element \"{$name}\" does not exist in the config");
        }

        return $this->config[$name];
    }

    /**
     * Returns TRUE if the element in the config has the specified value, or FALSE otherwise
     *
     * If $value is not passed, this method will return TRUE if the specified element has _any_ value, or FALSE if the
     * element is not set
     *
     * For strict checking, set $strict to TRUE - the default is FALSE
     */
    public function hasConfigValue(string $name, mixed $value = null, bool $strict = false): bool
    {
        if (isset($this->config[$name])) {
            if (func_num_args() < 2) {
                return true;
            }

            if ($strict) {
                return $this->config[$name] === $value;
            }

            return $this->config[$name] == $value;
        }

        return false;
    }

    /**
     * Sets the array of registered tasks
     *
     * @phpstan-param array<string, Task> $registeredTask
     */
    public function setRegisteredTasks(array $registeredTask): void
    {
        $this->registeredTask = $registeredTask;
    }

    /**
     * Returns an array containing the registered tasks
     *
     * @phpstan-return array<string, Task>
     */
    public function getRegisteredTasks(): array
    {
        return $this->registeredTask;
    }

    /**
     * Returns TRUE if the specified Task-class is registered, or FALSE otherwise
     *
     * @param  string $className
     * @return bool
     */
    public function taskClassIsRegistered($className): bool
    {
        return isset($this->registeredTask[$className]);
    }

    /**
     * Returns TRUE if a task with the specified name is registered, or FALSE otherwise
     *
     * If a matching task is found, $className is set with the name of the implementing class
     *
     * @param       string      $taskName
     * @param       string|null $className
     * @phpstan-param class-string<Task>|null $className
     * @return      bool
     */
    public function taskNameIsRegistered(string $taskName, ?string &$className = null): bool
    {
        foreach ($this->getRegisteredTasks() as $currClassName => $task) {
            if ($task->getTaskName() == $taskName) {
                $className = $currClassName;
                return true;
            }
        }

        return false;
    }

    public function setTaskInstance(Task $task): void
    {
        $this->taskInstance = $task;
    }

    public function getTaskInstance(): Task
    {
        return $this->taskInstance;
    }

    /**
     * Called by the constructor, this method includes and registers Doctrine core Tasks and then registers all other
     * loaded Task classes
     *
     * The second round of registering will pick-up loaded custom Tasks.  Methods are provided that will allow users to
     * register Tasks loaded after creating an instance of Cli.
     */
    protected function includeAndRegisterTaskClasses(): void
    {
        $this->includeAndRegisterDoctrineTaskClasses();

        //Always autoregister custom tasks _unless_ we've been explicitly asked not to
        if ($this->getConfigValue('autoregister_custom_tasks', true)) {
            $this->registerIncludedTaskClasses();
        }
    }

    /**
     * Includes and registers Doctrine-style tasks from the specified directory / directories
     *
     * If no directory is given it looks in the default Doctrine1/Task folder for the core tasks
     *
     * @param string|string[] $directories Can be a string path or array of paths
     */
    protected function includeAndRegisterDoctrineTaskClasses(string|array|null $directories = null): void
    {
        if ($directories === null) {
            $directories = Core::getPath() . '/src/Task';
        }

        foreach ((array) $directories as $directory) {
            foreach ($this->includeDoctrineTaskClasses($directory) as $className) {
                $this->registerTaskClass($className);
            }
        }
    }

    /**
     * Attempts to include Doctrine-style Task-classes from the specified directory - and nothing more besides
     *
     * Returns an array containing the names of Task classes included
     *
     * This method effectively makes two assumptions:
     * - The directory contains only _Task_ class-files
     * - The class files, and the class in each, follow the Doctrine naming conventions
     *
     * This means that a file called "Foo.php", say, will be expected to contain a Task class called
     * "Task\Foo".  Hence the method's name, "include*Doctrine*TaskClasses".
     *
     * @phpstan-return list<class-string<Task>> $taskClassesIncluded
     * @throws \InvalidArgumentException If the directory does not exist
     */
    protected function includeDoctrineTaskClasses(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("The directory \"{$directory}\" does not exist");
        }

        $taskClassesIncluded = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            $baseName = $file->getFileName();
            $matched = (bool) preg_match('/^([A-Z].*?)\.php$/', $baseName, $matches);

            if (!$matched) {
                continue;
            }

            $expectedClassName = self::TASK_BASE_CLASS . '\\' . $matches[1];

            if (class_exists($expectedClassName) && $this->classIsTask($expectedClassName)) {
                /** @phpstan-var class-string<Task> $expectedClassName */
                $taskClassesIncluded[] = $expectedClassName;
            }
        }

        return $taskClassesIncluded;
    }

    /**
     * Registers the specified _included_ task-class
     *
     * @phpstan-param class-string<Task> $className
     *
     * @throws \InvalidArgumentException If the class does not exist or the task-name is blank
     * @throws \DomainException If the class is not a Doctrine Task
     */
    public function registerTaskClass(string $className): void
    {
        // Simply ignore registered classes
        if ($this->taskClassIsRegistered($className)) {
            return;
        }

        if (!class_exists($className/*, false*/)) {
            throw new \InvalidArgumentException("The task class \"{$className}\" does not exist");
        }

        if (!$this->classIsTask($className)) {
            throw new \DomainException("The class \"{$className}\" is not a Doctrine Task");
        }

        $this->registeredTask[$className] = $this->createTaskInstance($className, $this);
    }

    /**
     * Returns TRUE if the specified class is a Task, or FALSE otherwise
     *
     * @phpstan-param class-string $className
     * @phpstan-assert-if-true class-string<Task> $className
     */
    protected function classIsTask(string $className): bool
    {
        return is_subclass_of($className, self::TASK_BASE_CLASS);
    }

    /**
     * Creates, and returns, a new instance of the specified Task class
     *
     * Displays a message, and returns FALSE, if there were problems instantiating the class
     *
     * @param       Cli $cli       Cli
     * @return      Task
     * @phpstan-template T of Task
     * @phpstan-param class-string<T> $className
     * @phpstan-return T
     */
    protected function createTaskInstance(string $className, Cli $cli): Task
    {
        return new $className($cli);
    }

    /**
     * Registers all loaded classes - by default - or the specified loaded Task classes
     *
     * This method will skip registered task classes, so it can be safely called many times over
     */
    public function registerIncludedTaskClasses(): void
    {
        foreach (get_declared_classes() as $className) {
            if ($this->classIsTask($className)) {
                /** @phpstan-var class-string<Task> $className */
                $this->registerTaskClass($className);
            }
        }
    }

    /**
     * Notify the formatter of a message
     *
     * @param  ?string $notification The notification message
     * @param  string $style        Style to format the notification with(INFO, ERROR)
     */
    public function notify(?string $notification = null, string $style = 'HEADER'): void
    {
        $formatter = $this->getFormatter();

        echo $formatter->format($this->getTaskInstance()->getTaskName() ?? '', 'INFO') . ' - ' .
            $formatter->format($notification ?? '', $style) . "\n";
    }

    /**
     * Formats, and then returns, the message in the specified exception
     */
    protected function formatExceptionMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (Core::debug()) {
            $message .= "\n" . $exception->getTraceAsString();
        }

        return $this->getFormatter()->format($message, 'ERROR') . "\n";
    }

    /**
     * Notify the formatter of an exception
     *
     * N.B. This should really only be called by Cli::run().  Exceptions should be thrown when errors occur:
     * it's up to Cli::run() to determine how those exceptions are reported.
     */
    protected function notifyException(Throwable $exception): void
    {
        $s = $this->formatExceptionMessage($exception);
        if (defined('STDERR')) {
            fwrite(STDERR, $s);
            return;
        }
        echo $s;
    }

    /**
     * Public function to run the loaded task with the passed arguments
     *
     * @param  string[] $args
     * @throws Cli\Exception
     * @todo   Should know more about what we're attempting to run so feedback can be improved. Continue refactoring.
     */
    public function run(array $args): void
    {
        try {
            $this->scriptName = $args[0];
            $requestedTaskName = $args[1] ?? null;

            if (!$requestedTaskName || $requestedTaskName == 'help') {
                $this->printTasks(null, $requestedTaskName == 'help' ? true : false);
                return;
            }

            if (isset($args[2]) && $args[2] === 'help') {
                $this->printTasks($requestedTaskName, true);
                return;
            }

            $this->taskNameIsRegistered($requestedTaskName, $taskClassName);
            /** @phpstan-var class-string<Task>|null $taskClassName */
            if ($taskClassName === null) {
                throw new Cli\Exception("The task \"{$requestedTaskName}\" has not been registered");
            }

            $taskInstance = $this->createTaskInstance($taskClassName, $this);
            $this->setTaskInstance($taskInstance);
            $this->executeTask($taskInstance, $this->prepareArgs(array_slice($args, 2)));
        } catch (Throwable $exception) {
            // Do not rethrow exceptions by default
            if ($this->getConfigValue('rethrow_exceptions', false)) {
                throw $exception;
            }

            $this->notifyException($exception);

            //User error
            if ($exception instanceof Cli\Exception) {
                $this->printTasks();
            }
        }
    }

    /**
     * Executes the task with the specified _prepared_ arguments
     *
     * @param string[]         $preparedArguments
     * @throws Cli\Exception If required arguments are missing
     */
    protected function executeTask(Task $task, array $preparedArguments): void
    {
        $task->setArguments($preparedArguments);

        if (!$task->validate()) {
            throw new Cli\Exception('Required arguments missing');
        }

        $task->execute();
    }

    /**
     * Prepare the raw arguments for execution. Combines with the required and optional argument
     * list in order to determine a complete array of arguments for the task
     *
     * @param  string[] $args Array of raw arguments
     * @return string[] $prepared  Array of prepared arguments
     * @todo   Continue refactoring for testing
     */
    protected function prepareArgs(array $args): array
    {
        $taskInstance = $this->getTaskInstance();

        $args = array_values($args);

        // First lets load populate an array with all the possible arguments. required and optional
        $prepared = [];

        $requiredArguments = $taskInstance->getRequiredArguments();
        foreach ($requiredArguments as $key => $arg) {
            $prepared[$arg] = null;
        }

        $optionalArguments = $taskInstance->getOptionalArguments();
        foreach ($optionalArguments as $key => $arg) {
            $prepared[$arg] = null;
        }

        // If we have a config array then lets try and fill some of the arguments with the config values
        foreach ($this->getConfig() as $key => $value) {
            if (array_key_exists($key, $prepared)) {
                $prepared[$key] = $value;
            }
        }

        // Now lets fill in the entered arguments to the prepared array
        $copy = $args;
        foreach ($prepared as $key => $value) {
            if (!$value && !empty($copy)) {
                $prepared[$key] = $copy[0];
                unset($copy[0]);
                $copy = array_values($copy);
            }
        }

        return $prepared;
    }

    /**
     * Prints an index of all the available tasks in the CLI instance
     */
    public function printTasks(?string $taskName = null, bool $full = false): void
    {
        $formatter = $this->getFormatter();
        $config    = $this->getConfig();

        $taskIndex = $formatter->format('Doctrine Command Line Interface', 'HEADER') . "\n\n";

        foreach ($this->getRegisteredTasks() as $task) {
            if ($taskName && strtolower($taskName) != strtolower($task->getTaskName() ?? '')) {
                continue;
            }

            $taskIndex .= $formatter->format($this->scriptName . ' ' . $task->getTaskName(), 'INFO');

            if ($full) {
                $taskIndex .= ' - ' . $task->getDescription() . "\n";

                $args = '';
                $args .= $this->assembleArgumentList($task->getRequiredArgumentsDescriptions(), $config, $formatter);
                $args .= $this->assembleArgumentList($task->getOptionalArgumentsDescriptions(), $config, $formatter);

                if ($args) {
                    $taskIndex .= "\n" . $formatter->format('Arguments:', 'HEADER') . "\n" . $args;
                }
            }

            $taskIndex .= "\n";
        }

        echo $taskIndex;
    }

    /**
     * @param  array                  $argumentsDescriptions
     * @param  array                  $config
     * @param  Cli\Formatter $formatter
     * @return string
     */
    protected function assembleArgumentList(array $argumentsDescriptions, array $config, Cli\Formatter $formatter)
    {
        $argumentList = '';

        foreach ($argumentsDescriptions as $name => $description) {
            $argumentList .= $formatter->format($name, 'ERROR') . ' - ';

            if (isset($config[$name])) {
                $argumentList .= $formatter->format($config[$name], 'COMMENT');
            } else {
                $argumentList .= $description;
            }

            $argumentList .= "\n";
        }

        return $argumentList;
    }

    /**
     * Used by Cli::loadTasks() and Cli::getLoadedTasks() to re-create their pre-refactoring behaviour
     *
     * @ignore
     * @phpstan-param array<string, Task> $registeredTask
     * @phpstan-return array<string, string>
     */
    private function createOldStyleTaskList(array $registeredTask): array
    {
        $taskNames = [];

        foreach ($registeredTask as $className => $task) {
            $taskName             = $task->getTaskName();
            if ($taskName !== null) {
                $taskNames[$taskName] = $taskName;
            }
        }

        return $taskNames;
    }

    /**
     * Old method retained for backwards compatibility
     *
     * @deprecated
     *
     * @param string $directory
     *
     * @return array
     */
    public function loadTasks($directory = null)
    {
        $this->includeAndRegisterDoctrineTaskClasses($directory);
        return $this->createOldStyleTaskList($this->getRegisteredTasks());
    }

    /**
     * Old method retained for backwards compatibility
     *
     * @deprecated
     *
     * @return string
     */
    protected function getTaskClassFromArgs(array $args)
    {
        return self::TASK_BASE_CLASS . '\\' . Inflector::classify(str_replace('-', '\\', $args[1]));
    }

    /**
     * Old method retained for backwards compatibility
     *
     * @deprecated
     *
     * @return array
     */
    public function getLoadedTasks()
    {
        return $this->createOldStyleTaskList($this->getRegisteredTasks());
    }
}
