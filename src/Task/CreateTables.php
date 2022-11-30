<?php

namespace Doctrine1\Task;

class CreateTables extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Create tables for all existing database connections. If table exists nothing happens.';

    /**
     * @var array
     */
    public $requiredArguments = ['models_path' => 'Specify path to your models directory.'];

    /**
     * @var array
     */
    public $optionalArguments = [];

    public function execute()
    {
        \Doctrine1\Core::createTablesFromModels($this->getArgument('models_path'));

        $this->notify('Created tables successfully');
    }
}
