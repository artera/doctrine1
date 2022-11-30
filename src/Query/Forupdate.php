<?php

namespace Doctrine1\Query;

class Forupdate extends \Doctrine1\Query\Part
{
    public function parse(mixed $forUpdate): bool
    {
        return (bool) $forUpdate;
    }
}
