<?php

namespace Doctrine1\Query;

class Nowait extends \Doctrine1\Query\Part
{
    public function parse(mixed $noWait): bool
    {
        return (bool) $noWait;
    }
}
