<?php

namespace Doctrine1\Query;

class Offset extends \Doctrine1\Query\Part
{
    /**
     * @param  string|int $offset
     * @return int
     */
    public function parse(string|int $offset): int
    {
        return (int) $offset;
    }
}
