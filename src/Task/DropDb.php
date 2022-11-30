<?php

namespace Doctrine1\Task;

class DropDb extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Drop database for all existing connections';

    /**
     * @var array
     */
    public $requiredArguments = [];

    /**
     * @var array
     */
    public $optionalArguments = ['force' => 'Whether or not to force the drop database task'];

    public function execute()
    {
        if (!$this->getArgument('force')) {
            $answer = $this->ask('Are you sure you wish to drop your databases? (y/n)');

            if ($answer != 'y') {
                $this->notify('Successfully cancelled');

                return;
            }
        }

        $manager = \Doctrine1\Manager::getInstance();
        foreach ($manager as $name => $connection) {
            try {
                $connection->dropDatabase();
                $this->notify("Successfully dropped database for connection named '" . $name . "'");
            } catch (\Throwable $e) {
                $this->notify($e->getMessage());
            }
        }
    }
}
