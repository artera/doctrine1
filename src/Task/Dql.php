<?php

namespace Doctrine1\Task;

use Doctrine1\HydrationMode;

class Dql extends \Doctrine1\Task
{
    /**
     * @var string
     */
    public $description = 'Execute dql query and display the results';

    /**
     * @var array
     */
    public $requiredArguments = ['models_path'    => 'Specify path to your \Doctrine1\Record definitions.',
                                           'dql_query' => 'Specify the complete dql query to execute.'];

    /**
     * @var array
     */
    public $optionalArguments = ['params' => 'Comma separated list of the params to replace the ? tokens in the dql'];

    public function execute()
    {
        \Doctrine1\Core::loadModels($this->getArgument('models_path'));

        $dql = $this->getArgument('dql_query');

        $query = \Doctrine1\Query::create();

        $params = $this->getArgument('params');
        $params = $params ? explode(',', $params) : [];

        $this->notify('executing: "' . $dql . '" (' . implode(', ', $params) . ')');

        $results = $query->query($dql, $params, HydrationMode::Array);

        $this->printResults($results);
    }

    /**
     * @param  array $array
     * @return void
     */
    protected function printResults($array)
    {
        $yaml  = \Doctrine1\Parser::dump($array, 'yml');
        $lines = explode("\n", (string) $yaml);

        unset($lines[0]);

        foreach ($lines as $yamlLine) {
            $line = trim($yamlLine);

            if ($line) {
                $this->notify($yamlLine);
            }
        }
    }
}
