<?php

namespace Doctrine1\Task;

class GenerateMigration extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Generate new migration class definition';

    /**
     * @var array
     */
    public $requiredArguments = ['class_name'           => 'Name of the migration class to generate',
                                           'migrations_path' => 'Specify the complete path to your migration classes folder.'];

    /**
     * @var array
     */
    public $optionalArguments = [];

    public function execute()
    {
        \Doctrine1\Core::generateMigrationClass($this->getArgument('class_name'), $this->getArgument('migrations_path'));

        $this->notify(sprintf('Generated migration class: %s successfully to %s', $this->getArgument('class_name'), $this->getArgument('migrations_path')));
    }
}
