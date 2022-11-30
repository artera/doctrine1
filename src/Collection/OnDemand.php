<?php

namespace Doctrine1\Collection;

/**
 * @template T of \Doctrine1\Record
 * @implements \Iterator<T>
 */
class OnDemand implements \Iterator
{
    protected \Doctrine1\Connection\Statement $stmt;
    protected mixed $current;
    protected array $tableAliasMap;
    protected \Doctrine1\Hydrator\AbstractHydrator $hydrator;
    protected int $index;

    /**
     * @param \Doctrine1\Hydrator\AbstractHydrator $hydrator
     * @param array                      $tableAliasMap
     */
    public function __construct(\Doctrine1\Connection\Statement $stmt, $hydrator, $tableAliasMap)
    {
        $this->stmt          = $stmt;
        $this->hydrator      = $hydrator;
        $this->tableAliasMap = $tableAliasMap;
        $this->current       = null;
        $this->index          = 0;

        $this->hydrateCurrent();
    }

    private function hydrateCurrent(): void
    {
        $record = $this->hydrator->hydrateResultSet($this->stmt);
        if ($record instanceof \Doctrine1\Collection) {
            $this->current = $record->getFirst();
        } elseif (is_array($record) && count($record) == 0) {
            $this->current = null;
        } elseif (is_array($record) && isset($record[0])) {
            $this->current = $record[0];
        } else {
            $this->current = $record;
        }
    }

    public function rewind(): void
    {
        $this->index = 0;
        $this->stmt->closeCursor();
        $this->stmt->execute();
        $this->hydrator->onDemandReset();
        $this->hydrateCurrent();
    }

    public function key(): int
    {
        return $this->index;
    }

    public function current(): mixed
    {
        return $this->current;
    }

    public function next(): void
    {
        $this->current = null;
        $this->index++;
        $this->hydrateCurrent();
    }

    public function valid(): bool
    {
        if ($this->current !== null && $this->current !== false) {
            return true;
        }
        return false;
    }
}
