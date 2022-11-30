<?php

namespace Doctrine1\Task;

class GenerateSql extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Generate sql for all existing database connections.';

    /**
     * @var array
     */
    public $requiredArguments = ['models_path'   => 'Specify complete path to your \Doctrine1\Record definitions.',
                                           'sql_path' => 'Path to write the generated sql.'];

    /**
     * @var array
     */
    public $optionalArguments = [];

    public function execute()
    {
        if (is_dir($this->getArgument('sql_path'))) {
            $path = $this->getArgument('sql_path') . DIRECTORY_SEPARATOR . 'schema.sql';
        } elseif (is_file($this->getArgument('sql_path'))) {
            $path = $this->getArgument('sql_path');
        } else {
            throw new \Doctrine1\Task\Exception('Invalid sql path.');
        }

        $sql = \Doctrine1\Core::generateSqlFromModels($this->getArgument('models_path'));

        file_put_contents($path, $sql);

        $this->notify('Generated SQL successfully for models');
    }
}
