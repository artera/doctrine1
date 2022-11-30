<?php

namespace Doctrine1\Task;

class BuildAllLoad extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Calls build-all, and load-data';

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
     * @var \Doctrine1\Task\BuildAll
     */
    public $buildAll;

    /**
     * @var \Doctrine1\Task\LoadData
     */
    public $loadData;

    /**
     * @param \Doctrine1\Cli $dispatcher
     */
    public function __construct($dispatcher = null)
    {
        parent::__construct($dispatcher);

        $this->buildAll = new \Doctrine1\Task\BuildAll($this->dispatcher);
        $this->loadData = new \Doctrine1\Task\LoadData($this->dispatcher);

        $this->requiredArguments = array_merge($this->requiredArguments, $this->buildAll->requiredArguments, $this->loadData->requiredArguments);
        $this->optionalArguments = array_merge($this->optionalArguments, $this->buildAll->optionalArguments, $this->loadData->optionalArguments);
    }

    public function execute()
    {
        $this->buildAll->setArguments($this->getArguments());
        $this->buildAll->execute();

        $this->loadData->setArguments($this->getArguments());
        $this->loadData->execute();
    }
}
