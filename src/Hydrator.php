<?php

namespace Doctrine1;

/**
 * Its purpose is to populate object graphs.
 */
class Hydrator
{
    protected static int $totalHydrationTime = 0;

    protected array $hydrators;

    protected ?string $rootAlias = null;

    /** @phpstan-var int|class-string<Hydrator\AbstractHydrator> */
    protected int|string $hydrationMode = Core::HYDRATE_RECORD;

    /**
     * @phpstan-var array<string, array{table: Table, map: ?string, parent?: string, relation?: Relation, ref?: bool, agg?: array<string, string>}>
     */
    protected array $queryComponents = [];

    public function __construct()
    {
        $this->hydrators = Manager::getInstance()->getHydrators();
    }

    /**
     * Get the hydration mode
     *
     * @phpstan-return int|class-string<Hydrator\AbstractHydrator>
     * @return int|string $hydrationMode One of the Core::HYDRATE_* constants
     */
    public function getHydrationMode(): int|string
    {
        return $this->hydrationMode;
    }

    /**
     * Set the hydration mode
     *
     * @phpstan-param int|class-string<Hydrator\AbstractHydrator> $hydrationMode
     * @param int|string $hydrationMode One of the Core::HYDRATE_* constants or
     *                             a string representing the name of the hydration
     *                             mode or or an instance of the hydration class
     */
    public function setHydrationMode(int|string $hydrationMode): void
    {
        $this->hydrationMode = $hydrationMode;
    }

    /**
     * Set the array of query components
     *
     * @param array $queryComponents
     */
    public function setQueryComponents(array $queryComponents): void
    {
        $this->queryComponents = $queryComponents;
    }

    /**
     * Get the array of query components
     *
     * @phpstan-return array<string, array{table: Table, map: ?string, parent?: string, relation?: Relation, ref?: bool}>
     */
    public function getQueryComponents(): array
    {
        return $this->queryComponents;
    }

    /**
     * Get the name of the driver class for the passed hydration mode
     *
     * @phpstan-param int|class-string<Hydrator\AbstractHydrator>|null $mode
     * @phpstan-return Hydrator\AbstractHydrator|class-string<Hydrator\AbstractHydrator>
     */
    public function getHydratorDriverClassName(int|string|null $mode = null): string|Hydrator\AbstractHydrator
    {
        if ($mode === null) {
            $mode = $this->hydrationMode;
        }

        if (!isset($this->hydrators[$mode])) {
            throw new Hydrator\Exception('Invalid hydration mode specified: ' . $this->hydrationMode);
        }

        return $this->hydrators[$mode];
    }

    /**
     * Get an instance of the hydration driver for the passed hydration mode
     * @phpstan-param int|class-string<Hydrator\AbstractHydrator> $mode
     */
    public function getHydratorDriver(int|string $mode, array $tableAliases): Hydrator\AbstractHydrator
    {
        $driverClass = $this->getHydratorDriverClassName($mode);
        if (is_object($driverClass)) {
            if (!$driverClass instanceof Hydrator\AbstractHydrator) {
                throw new Hydrator\Exception('Invalid hydration class specified: ' . get_class($driverClass));
            }
            $driver = $driverClass;
            $driver->setQueryComponents($this->queryComponents);
            $driver->setTableAliases($tableAliases);
            $driver->setHydrationMode($mode);
        } else {
            $driver = new $driverClass($this->queryComponents, $tableAliases, $mode);
        }

        return $driver;
    }

    /**
     * Hydrate the query statement in to its final data structure by one of the
     * hydration drivers.
     */
    public function hydrateResultSet(Connection\Statement $stmt, array $tableAliases): mixed
    {
        $driver = $this->getHydratorDriver($this->hydrationMode, $tableAliases);
        return $driver->hydrateResultSet($stmt);
    }
}
