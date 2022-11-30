<?php

namespace Doctrine1\Task;

class GenerateYamlDb extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Generates a Yaml schema file from an existing database';

    /**
     * @var array
     */
    public $requiredArguments = ['yaml_schema_path' => 'Specify the path to your yaml schema files.'];

    /**
     * @var array
     */
    public $optionalArguments = [];

    public function execute()
    {
        \Doctrine1\Core::generateYamlFromDb($this->getArgument('yaml_schema_path'));

        $this->notify('Generate YAML schema successfully from database');
    }
}
