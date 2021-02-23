<?php

class Doctrine_Query_Offset extends Doctrine_Query_Part
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
