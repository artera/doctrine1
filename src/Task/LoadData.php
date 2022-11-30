<?php

namespace Doctrine1\Task;

class LoadData extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Load data from a yaml data fixture file.';

    /**
     * @var array
     */
    public $requiredArguments = ['data_fixtures_path' => 'Specify the complete path to load the yaml data fixtures files from.',
                                           'models_path'   => 'Specify path to your \Doctrine1\Record definitions.'];

    /**
     * @var array
     */
    public $optionalArguments = ['append' => 'Whether or not to append the data'];

    /**
     * @return void
     */
    public function execute()
    {
        \Doctrine1\Core::loadModels($this->getArgument('models_path'));
        \Doctrine1\Core::loadData($this->getArgument('data_fixtures_path'), $this->getArgument('append', false));

        $this->notify('Data was successfully loaded');
    }
}
