<?php

namespace Doctrine1\Task;

class RebuildDb extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Drops and re-creates databases';

    /**
     * @var array
     */
    public $requiredArguments = [];

    /**
     * @var array
     */
    public $optionalArguments = [];

    // These were undefined, added for static analysis and set to public so api isn't changed
    /**
     * @var \Doctrine1\Task\CreateDb
     */
    public $createDb;

    /**
     * @var \Doctrine1\Task\CreateTables
     */
    public $createTables;

    /**
     * @var \Doctrine1\Task\DropDb
     */
    public $dropDb;

    /**
     * @param \Doctrine1\Cli $dispatcher
     */
    public function __construct($dispatcher = null)
    {
        parent::__construct($dispatcher);

        $this->dropDb       = new \Doctrine1\Task\DropDb($this->dispatcher);
        $this->createDb     = new \Doctrine1\Task\CreateDb($this->dispatcher);
        $this->createTables = new \Doctrine1\Task\CreateTables($this->dispatcher);

        $this->requiredArguments = array_merge($this->requiredArguments, $this->dropDb->requiredArguments, $this->createDb->requiredArguments, $this->createTables->requiredArguments);
        $this->optionalArguments = array_merge($this->optionalArguments, $this->dropDb->optionalArguments, $this->createDb->optionalArguments, $this->createTables->optionalArguments);
    }

    public function execute()
    {
        $this->dropDb->setArguments($this->getArguments());
        $this->dropDb->execute();

        $this->createDb->setArguments($this->getArguments());
        $this->createDb->execute();

        $this->createTables->setArguments($this->getArguments());
        $this->createTables->execute();
    }
}
