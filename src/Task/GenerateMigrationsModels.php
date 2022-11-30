<?php

namespace Doctrine1\Task;

class GenerateMigrationsModels extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Generate migration classes for an existing set of models';

    /**
     * @var array
     */
    public $requiredArguments = ['migrations_path'  => 'Specify the path to your migration classes folder.',
                                           'models_path' => 'Specify the path to your doctrine models folder.'];

    /**
     * @var array
     */
    public $optionalArguments = [];

    public function execute()
    {
        \Doctrine1\Core::generateMigrationsFromModels($this->getArgument('migrations_path'), $this->getArgument('models_path'));

        $this->notify('Generated migration classes successfully from models');
    }
}
