<?php

namespace Doctrine1\Task;

class CreateDb extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Create all databases for your connections. If the database already exists, nothing happens.';

    /**
     * @var array
     */
    public $optionalArguments = [];

    public function execute()
    {
        $manager = \Doctrine1\Manager::getInstance();
        foreach ($manager as $name => $connection) {
            try {
                $manager->setCurrentConnection($name);
                $connection->createDatabase();
                $this->notify("Successfully created database for connection named '$name'");
            } catch (\Throwable $e) {
                $this->notify($e->getMessage());
            }
        }
    }
}
