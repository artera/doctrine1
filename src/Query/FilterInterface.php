<?php

namespace Doctrine1\Query;

interface FilterInterface
{

    /**
     * preQuery
     *
     * Method for listening the preQuery method of \Doctrine1\Query and
     * hooking into the query building procedure, doing any custom / specialized
     * query building procedures that are neccessary.
     *
     * @return void
     */
    public function preQuery(\Doctrine1\Query $query);

    /**
     * postQuery
     *
     * Method for listening the postQuery method of \Doctrine1\Query and
     * to hook into the query building procedure, doing any custom / specialized
     * post query procedures (for example logging) that are neccessary.
     *
     * @return void
     */
    public function postQuery(\Doctrine1\Query $query);
}
