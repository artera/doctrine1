<?php

namespace Doctrine1\Task;

class BuildAll extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Calls generate-models-from-yaml, create-db, and create-tables';

    /**
     * @var array
     */
    public $requiredArguments = [];

    /**
     * @var array
     */
    public $optionalArguments = [];

    /**
     * @var \Doctrine1\Task\GenerateModelsYaml
     */
    protected $models;

    /**
     * @var \Doctrine1\Task\CreateTables
     */
    protected $tables;

    // This was undefined, added for static analysis and set to public so api isn't changed
    /**
     * @var \Doctrine1\Task\CreateDb
     */
    public $createDb;

    /**
     * @param \Doctrine1\Cli $dispatcher
     */
    public function __construct($dispatcher = null)
    {
        parent::__construct($dispatcher);

        $this->models   = new \Doctrine1\Task\GenerateModelsYaml($this->dispatcher);
        $this->createDb = new \Doctrine1\Task\CreateDb($this->dispatcher);
        $this->tables   = new \Doctrine1\Task\CreateTables($this->dispatcher);

        $this->requiredArguments = array_merge($this->requiredArguments, $this->models->requiredArguments, $this->createDb->requiredArguments, $this->tables->requiredArguments);
        $this->optionalArguments = array_merge($this->optionalArguments, $this->models->optionalArguments, $this->createDb->optionalArguments, $this->tables->optionalArguments);
    }

    public function execute()
    {
        $this->models->setArguments($this->getArguments());
        $this->models->execute();

        $this->createDb->setArguments($this->getArguments());
        $this->createDb->execute();

        $this->tables->setArguments($this->getArguments());
        $this->tables->execute();
    }
}
