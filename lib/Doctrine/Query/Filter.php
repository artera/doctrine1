<?php

class Doctrine_Query_Filter implements Doctrine_Query_Filter_Interface
{
    /**
     * Method for listening the preQuery method of Doctrine_Query and
     * hooking into the query building procedure, doing any custom / specialized
     * query building procedures that are neccessary.
     *
     * @return void
     */
    public function preQuery(Doctrine_Query $query): void
    {
    }

    /**
     * Method for listening the postQuery method of Doctrine_Query and
     * to hook into the query building procedure, doing any custom / specialized
     * post query procedures (for example logging) that are neccessary.
     *
     * @param  Doctrine_Query $query
     * @return void
     */
    public function postQuery(Doctrine_Query $query): void
    {
    }
}
