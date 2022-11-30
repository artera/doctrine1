<?php

namespace Doctrine1\Query;

class Filter implements \Doctrine1\Query\FilterInterface
{
    /**
     * Method for listening the preQuery method of \Doctrine1\Query and
     * hooking into the query building procedure, doing any custom / specialized
     * query building procedures that are neccessary.
     *
     * @return void
     */
    public function preQuery(\Doctrine1\Query $query): void
    {
    }

    /**
     * Method for listening the postQuery method of \Doctrine1\Query and
     * to hook into the query building procedure, doing any custom / specialized
     * post query procedures (for example logging) that are neccessary.
     *
     * @param  \Doctrine1\Query $query
     * @return void
     */
    public function postQuery(\Doctrine1\Query $query): void
    {
    }
}
