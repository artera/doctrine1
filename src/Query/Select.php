<?php

namespace Doctrine1\Query;

class Select extends \Doctrine1\Query\Part
{
    public function parse(string $dql): void
    {
        $this->query->parseSelect($dql);
    }
}
