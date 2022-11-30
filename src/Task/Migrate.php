<?php

namespace Doctrine1\Task;

class Migrate extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Migrate database to latest version or the specified version';

    /**
     * @var array
     */
    public $requiredArguments = ['migrations_path' => 'Specify path to your migrations directory.'];

    /**
     * @var array
     */
    public $optionalArguments = ['version' => 'Version to migrate to. If you do not specify, the db will be migrated from the current version to the latest.'];

    /**
     * @return void
     */
    public function execute()
    {
        $version = \Doctrine1\Core::migrate($this->getArgument('migrations_path'), $this->getArgument('version'));

        $this->notify('migrated successfully to version #' . $version);
    }
}
