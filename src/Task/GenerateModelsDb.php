<?php

namespace Doctrine1\Task;

class GenerateModelsDb extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Generates your \Doctrine1\Record definitions from your existing database connections.';

    /**
     * @var array
     */
    public $requiredArguments = ['models_path' => 'Specify path to your \Doctrine1\Record definitions.'];

    /**
     * @var array
     */
    public $optionalArguments = ['connection' => 'Optionally specify a single connection to generate the models for.'];

    public function execute()
    {
        $configs = $this->dispatcher->getConfig();
        $options = $configs['generate_models_options'] ?? [];
        \Doctrine1\Core::generateModelsFromDb($this->getArgument('models_path'), (array) $this->getArgument('connection'), $options);

        $this->notify('Generated models successfully from databases');
    }
}
