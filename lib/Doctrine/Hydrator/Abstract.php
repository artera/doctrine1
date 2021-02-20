<?php

abstract class Doctrine_Hydrator_Abstract
{
    /**
     * @phpstan-var array<string, array{table: Doctrine_Table, map: ?string, parent?: string, relation?: Doctrine_Relation, ref?: bool, agg?: array<string, string>}>
     */
    protected array $_queryComponents = [];

    protected array $_tableAliases = [];

    protected ?array $_priorRow = null;

    /** @phpstan-var int|class-string<Doctrine_Hydrator_Abstract> */
    protected int|string $_hydrationMode;

    /**
     * @param array $queryComponents
     * @param array $tableAliases
     * @phpstan-param int|class-string<Doctrine_Hydrator_Abstract> $hydrationMode
     */
    public function __construct(array $queryComponents = [], array $tableAliases = [], int|string $hydrationMode = Doctrine_Core::HYDRATE_RECORD)
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
        $this->_queryComponents = $queryComponents;
    }

    /**
     * Set the table aliases for this query
     *
     * @param  array $tableAliases
     * @return void
     */
    public function setTableAliases($tableAliases)
    {
        $this->_tableAliases = $tableAliases;
    }

    /**
     * Get the hydration mode
     *
     * @phpstan-return int|class-string<Doctrine_Hydrator_Abstract>
     * @return int|string One of the Doctrine_Core::HYDRATE_* constants
     */
    public function getHydrationMode(): int|string
    {
        return $this->_hydrationMode;
    }

    /**
     * Set the hydration mode
     *
     * @phpstan-param int|class-string<Doctrine_Hydrator_Abstract> $hydrationMode
     * @param int|string $hydrationMode One of the Doctrine_Core::HYDRATE_* constants or
     *                              a string representing the name of the hydration
     *                              mode or or an instance of the hydration class
     */
    public function setHydrationMode(int|string $hydrationMode): void
    {
        $this->_hydrationMode = $hydrationMode;
    }

    /**
     * @return Doctrine_Table
     */
    public function getRootComponent()
    {
        $queryComponents = array_values($this->_queryComponents);
        return $queryComponents[0]['table'];
    }

    /**
     * @return void
     */
    public function onDemandReset()
    {
        $this->_priorRow = null;
    }

    /**
     * Checks whether a name is ignored. Used during result set parsing to skip
     * certain elements in the result set that do not have any meaning for the result.
     * (I.e. limit/offset emulation adds doctrine_rownum to the result set).
     *
     * @param  string $name
     * @return boolean
     */
    protected function _isIgnoredName($name)
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
    abstract public function hydrateResultSet(Doctrine_Connection_Statement $stmt): mixed;
}
