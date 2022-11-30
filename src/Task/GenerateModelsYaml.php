<?php

namespace Doctrine1\Task;

class GenerateModelsYaml extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Generates your \Doctrine1\Record definitions from a Yaml schema file';

    /**
     * @var array
     */
    public $requiredArguments = ['yaml_schema_path' => 'Specify the complete directory path to your yaml schema files.',
                                           'models_path' => 'Specify complete path to your \Doctrine1\Record definitions.'];
    /**
     * @var array
     */
    public $optionalArguments = ['generate_models_options' => 'Array of options for generating models'];

    public function execute()
    {
        \Doctrine1\Core::generateModelsFromYaml($this->getArgument('yaml_schema_path'), $this->getArgument('models_path'), $this->getArgument('generate_models_options', []));

        $this->notify('Generated models successfully from YAML schema');
    }
}
