<?php

class Doctrine_Query_Select extends Doctrine_Query_Part
{
    public function parse(string $dql): void
    {
        $this->query->parseSelect($dql);
    }
}
