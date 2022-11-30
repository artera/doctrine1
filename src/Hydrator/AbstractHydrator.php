<?php

namespace Doctrine1\Hydrator;

abstract class AbstractHydrator
{
    /**
     * @phpstan-var array<string, array{table: \Doctrine1\Table, map: ?string, parent?: string, relation?: \Doctrine1\Relation, ref?: bool, agg?: array<string, string>}>
     */
    protected array $queryComponents = [];

    protected array $tableAliases = [];

    protected ?array $priorRow = null;

    /** @phpstan-var int|class-string<\Doctrine1\Hydrator\AbstractHydrator> */
    protected int|string $hydrationMode;

    /**
     * @param array $queryComponents
     * @param array $tableAliases
     * @phpstan-param int|class-string<\Doctrine1\Hydrator\AbstractHydrator> $hydrationMode
     */
    public function __construct(array $queryComponents = [], array $tableAliases = [], int|string $hydrationMode = \Doctrine1\Core::HYDRATE_RECORD)
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
     * @phpstan-return int|class-string<\Doctrine1\Hydrator\AbstractHydrator>
     * @return int|string One of the \Doctrine1\Core::HYDRATE_* constants
     */
    public function getHydrationMode(): int|string
    {
        return $this->hydrationMode;
    }

    /**
     * Set the hydration mode
     *
     * @phpstan-param int|class-string<\Doctrine1\Hydrator\AbstractHydrator> $hydrationMode
     * @param int|string $hydrationMode One of the \Doctrine1\Core::HYDRATE_* constants or
     *                              a string representing the name of the hydration
     *                              mode or or an instance of the hydration class
     */
    public function setHydrationMode(int|string $hydrationMode): void
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
