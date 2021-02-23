<?php

class Doctrine_Query_Limit extends Doctrine_Query_Part
{
    /**
     * @param  string|int $limit
     * @return int
     */
    public function parse(string|int $limit): int
    {
        return (int) $limit;
    }
}
