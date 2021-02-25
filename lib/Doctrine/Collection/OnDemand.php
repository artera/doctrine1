<?php

/**
 * @template T of Doctrine_Record
 * @implements Iterator<T>
 */
class Doctrine_Collection_OnDemand implements Iterator
{
    protected Doctrine_Connection_Statement $stmt;

    /**
     * @var mixed
     */
    protected $current;

    /**
     * @var array
     */
    protected $tableAliasMap;

    /**
     * @var Doctrine_Hydrator_Abstract
     */
    protected $hydrator;

    /**
     * @var int
     */
    protected $index;

    /**
     * @param Doctrine_Hydrator_Abstract                        $hydrator
     * @param array                                             $tableAliasMap
     */
    public function __construct(Doctrine_Connection_Statement $stmt, $hydrator, $tableAliasMap)
    {
        $this->stmt          = $stmt;
        $this->hydrator      = $hydrator;
        $this->tableAliasMap = $tableAliasMap;
        $this->current       = null;
        $this->index          = 0;

        $this->hydrateCurrent();
    }

    /**
     * @return void
     */
    private function hydrateCurrent()
    {
        $record = $this->hydrator->hydrateResultSet($this->stmt);
        if ($record instanceof Doctrine_Collection) {
            $this->current = $record->getFirst();
        } elseif (is_array($record) && count($record) == 0) {
            $this->current = null;
        } elseif (is_array($record) && isset($record[0])) {
            $this->current = $record[0];
        } else {
            $this->current = $record;
        }
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->index = 0;
        $this->stmt->closeCursor();
        $this->stmt->execute();
        $this->hydrator->onDemandReset();
        $this->hydrateCurrent();
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->current = null;
        $this->index++;
        $this->hydrateCurrent();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        if (!is_null($this->current) && $this->current !== false) {
            return true;
        }
        return false;
    }
}
