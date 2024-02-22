<?php

namespace Doctrine1\Query;

class Skiplocked extends \Doctrine1\Query\Part
{
    public function parse(mixed $skipLocked): bool
    {
        return (bool) $skipLocked;
    }
}
