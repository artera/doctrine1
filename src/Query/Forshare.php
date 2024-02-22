<?php

namespace Doctrine1\Query;

class Forshare extends \Doctrine1\Query\Part
{
    public function parse(mixed $forShare): bool
    {
        return (bool) $forShare;
    }
}
