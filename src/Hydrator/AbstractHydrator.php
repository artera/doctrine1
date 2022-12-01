<?php

namespace Doctrine1\Hydrator;

use Doctrine1\HydrationMode;

abstract class AbstractHydrator
{
    /**
     * @phpstan-var array<string, array{table: \Doctrine1\Table, map: ?string, parent?: string, relation?: \Doctrine1\Relation, ref?: bool, agg?: array<string, string>}>
     */
    protected array $queryComponents = [];

    protected array $tableAliases = [];

    protected ?array $priorRow = null;

    /** @phpstan-var HydrationMode|class-string<\Doctrine1\Hydrator\AbstractHydrator> */
    protected HydrationMode|string $hydrationMode;

    /**
     * @param array $queryComponents
     * @param array $tableAliases
     * @phpstan-param HydrationMode|class-string<\Doctrine1\Hydrator\AbstractHydrator> $hydrationMode
     */
    public function __construct(array $queryComponents = [], array $tableAliases = [], HydrationMode|string $hydrationMode = HydrationMode::Record)
    {
        $this->setQueryComponents($queryComponents);
        $this->setTableAliases($tableAliases);
        $this->setHydrationMode($hydrationMode);
    }

    /**
     * Set the query components (structure and query instructions)
     *
     * @param  array $queryComponents
     * @return void
     */
    public function setQueryComponents(array $queryComponents): void
    {
        $this->queryComponents = $queryComponents;
    }

    /**
     * Set the table aliases for this query
     *
     * @param  array $tableAliases
     * @return void
     */
    public function setTableAliases($tableAliases)
    {
        $this->tableAliases = $tableAliases;
    }

    /**
     * Get the hydration mode
     *
     * @phpstan-return HydrationMode|class-string<\Doctrine1\Hydrator\AbstractHydrator>
     * @return HydrationMode|string One of the \Doctrine1\HydrationModes constants
     */
    public function getHydrationMode(): HydrationMode|string
    {
        return $this->hydrationMode;
    }

    /**
     * Set the hydration mode
     *
     * @phpstan-param HydrationMode|class-string<\Doctrine1\Hydrator\AbstractHydrator> $hydrationMode
     * @param HydrationMode|string $hydrationMode One of the \Doctrine1\HydrationModes constants or
     *                              a string representing the name of the hydration
     *                              mode or or an instance of the hydration class
     */
    public function setHydrationMode(HydrationMode|string $hydrationMode): void
    {
        $this->hydrationMode = $hydrationMode;
    }

    /**
     * @return void
     */
    public function onDemandReset()
    {
        $this->priorRow = null;
    }

    /**
     * Checks whether a name is ignored. Used during result set parsing to skip
     * certain elements in the result set that do not have any meaning for the result.
     * (I.e. limit/offset emulation adds doctrine_rownum to the result set).
     *
     * @param  string $name
     * @return boolean
     */
    protected function isIgnoredName($name)
    {
        return $name == 'DOCTRINE_ROWNUM';
    }

    /**
     * hydrateResultSet
     * parses the data returned by statement object
     *
     * This is method defines the core of Doctrine object population algorithm
     * hence this method strives to be as fast as possible
     *
     * The key idea is the loop over the rowset only once doing all the needed operations
     * within this massive loop.
     *
     * @return mixed
     */
    abstract public function hydrateResultSet(\Doctrine1\Connection\Statement $stmt): mixed;
}
