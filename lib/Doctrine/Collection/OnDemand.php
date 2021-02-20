<?php

/**
 * @template T of Doctrine_Record_Abstract
 * @implements Iterator<T>
 */
class Doctrine_Collection_OnDemand implements Iterator
{
    protected Doctrine_Connection_Statement $_stmt;

    /**
     * @var mixed
     */
    protected $_current;

    /**
     * @var array
     */
    protected $_tableAliasMap;

    /**
     * @var Doctrine_Hydrator_Abstract
     */
    protected $_hydrator;

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
        $this->_stmt          = $stmt;
        $this->_hydrator      = $hydrator;
        $this->_tableAliasMap = $tableAliasMap;
        $this->_current       = null;
        $this->index          = 0;

        $this->_hydrateCurrent();
    }

    /**
     * @return void
     */
    private function _hydrateCurrent()
    {
        $record = $this->_hydrator->hydrateResultSet($this->_stmt);
        if ($record instanceof Doctrine_Collection) {
            $this->_current = $record->getFirst();
        } elseif (is_array($record) && count($record) == 0) {
            $this->_current = null;
        } elseif (is_array($record) && isset($record[0])) {
            $this->_current = $record[0];
        } else {
            $this->_current = $record;
        }
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->index = 0;
        $this->_stmt->closeCursor();
        $this->_stmt->execute();
        $this->_hydrator->onDemandReset();
        $this->_hydrateCurrent();
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
        return $this->_current;
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->_current = null;
        $this->index++;
        $this->_hydrateCurrent();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        if (!is_null($this->_current) && $this->_current !== false) {
            return true;
        }
        return false;
    }
}
