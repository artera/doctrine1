<?php

namespace Doctrine1\Query\Filter;

class Chain
{
    /**
     * @var array $filters         an array of \Doctrine1\Query\Filter objects
     */
    protected $filters = [];

    /**
     * add
     *
     * @param  \Doctrine1\Query\Filter $filter
     * @return void
     */
    public function add(\Doctrine1\Query\Filter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * returns a \Doctrine1\Query\Filter on success
     * and null on failure
     *
     * @param  mixed $key
     * @return mixed
     */
    public function get($key)
    {
        if (!isset($this->filters[$key])) {
            throw new \Doctrine1\Query\Exception('Unknown filter ' . $key);
        }
        return $this->filters[$key];
    }

    /**
     * set
     *
     * @param  mixed                 $key
     * @param  \Doctrine1\Query\Filter $listener
     * @return void
     */
    public function set($key, \Doctrine1\Query\Filter $listener)
    {
        $this->filters[$key] = $listener;
    }

    /**
     * preQuery
     *
     * Method for listening the preQuery method of \Doctrine1\Query and
     * hooking into the query building procedure, doing any custom / specialized
     * query building procedures that are neccessary.
     *
     * @return void
     */
    public function preQuery(\Doctrine1\Query $query)
    {
        foreach ($this->filters as $filter) {
            $filter->preQuery($query);
        }
    }

    /**
     * postQuery
     *
     * Method for listening the postQuery method of \Doctrine1\Query and
     * to hook into the query building procedure, doing any custom / specialized
     * post query procedures (for example logging) that are neccessary.
     *
     * @return void
     */
    public function postQuery(\Doctrine1\Query $query)
    {
        foreach ($this->filters as $filter) {
            $filter->postQuery($query);
        }
    }
}
