<?php

namespace Doctrine1\Task;

class GenerateYamlModels extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Generates a Yaml schema file from existing \Doctrine1\Record definitions';

    /**
     * @var array
     */
    public $requiredArguments = ['yaml_schema_path' => 'Specify the complete directory path to your yaml schema files.'];

    /**
     * @var array
     */
    public $optionalArguments = ['models_path' => 'Specify complete path to your \Doctrine1\Record definitions.'];

    /**
     * @return void
     */
    public function execute()
    {
        \Doctrine1\Core::generateYamlFromModels($this->getArgument('yaml_schema_path'), $this->getArgument('models_path'));

        $this->notify('Generated YAML schema successfully from models');
    }
}
