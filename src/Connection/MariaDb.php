<?php

namespace Doctrine1\Connection;

class MariaDb extends Mysql
{
    protected function illuminateGrammar(): \Illuminate\Database\Query\Grammars\Grammar
    {
        return new \Illuminate\Database\Query\Grammars\MariaDbGrammar();
    }
}
