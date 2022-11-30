<?php

namespace Doctrine1\Query;

class Limit extends \Doctrine1\Query\Part
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
