<?php

namespace Doctrine1\Table;

/**
 * each record is added into \Doctrine1\Repository at the same time they are created,
 * loaded from the database or retrieved from the cache
 */
class Repository implements \Countable, \IteratorAggregate
{
    /**
     * @var \Doctrine1\Table $table
     */
    private $table;

    /**
     * @var array $registry
     * an array of all records
     * keys representing record object identifiers
     */
    private $registry = [];

    /**
     * constructor
     *
     * @param \Doctrine1\Table $table
     */
    public function __construct(\Doctrine1\Table $table)
    {
        $this->table = $table;
    }

    /**
     * getTable
     *
     * @return \Doctrine1\Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * add
     *
     * @param  \Doctrine1\Record $record record to be added into registry
     * @return boolean
     */
    public function add(\Doctrine1\Record $record)
    {
        $oid = $record->getOid();

        if (isset($this->registry[$oid])) {
            return false;
        }
        $this->registry[$oid] = $record;

        return true;
    }

    /**
     * get
     *
     * @param  integer $oid
     * @return mixed
     * @throws \Doctrine1\Table\Repository\Exception
     */
    public function get($oid)
    {
        if (!isset($this->registry[$oid])) {
            throw new \Doctrine1\Table\Repository\Exception('Unknown object identifier');
        }
        return $this->registry[$oid];
    }

    /**
     * count
     * \Doctrine1\Registry implements interface \Countable
     *
     * @return integer                      the number of records this registry has
     */
    public function count(): int
    {
        return count($this->registry);
    }

    /**
     * @param  integer $oid object identifier
     * @return boolean                      whether ot not the operation was successful
     */
    public function evict($oid)
    {
        if (!isset($this->registry[$oid])) {
            return false;
        }
        unset($this->registry[$oid]);
        return true;
    }

    /**
     * @return integer                      number of records evicted
     */
    public function evictAll()
    {
        $evicted = 0;
        foreach ($this->registry as $oid => $record) {
            if ($this->evict($oid)) {
                $evicted++;
            }
        }
        return $evicted;
    }

    /**
     * getIterator
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->registry);
    }

    /**
     * contains
     *
     * @param integer $oid object identifier
     *
     * @return bool
     */
    public function contains($oid)
    {
        return isset($this->registry[$oid]);
    }

    /**
     * loadAll
     *
     * @return void
     */
    public function loadAll()
    {
        $this->table->findAll();
    }
}
