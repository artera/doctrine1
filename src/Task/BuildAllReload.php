<?php

namespace Doctrine1\Task;

class BuildAllReload extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Calls rebuild-db and load-data';

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
     * @var \Doctrine1\Task\LoadData
     */
    public $loadData;

    /**
     * @var \Doctrine1\Task\RebuildDb
     */
    public $rebuildDb;

    /**
     * @param \Doctrine1\Cli $dispatcher
     */
    public function __construct($dispatcher = null)
    {
        parent::__construct($dispatcher);

        $this->rebuildDb = new \Doctrine1\Task\RebuildDb($this->dispatcher);
        $this->loadData  = new \Doctrine1\Task\LoadData($this->dispatcher);

        $this->requiredArguments = array_merge($this->requiredArguments, $this->rebuildDb->requiredArguments, $this->loadData->requiredArguments);
        $this->optionalArguments = array_merge($this->optionalArguments, $this->rebuildDb->optionalArguments, $this->loadData->optionalArguments);
    }

    public function execute()
    {
        $this->rebuildDb->setArguments($this->getArguments());
        $this->rebuildDb->execute();

        $this->loadData->setArguments($this->getArguments());
        $this->loadData->execute();
    }
}
