<?php

class Doctrine_Query_Forupdate extends Doctrine_Query_Part
{
    public function parse(mixed $forUpdate): bool
    {
        return (bool) $forUpdate;
    }
}
