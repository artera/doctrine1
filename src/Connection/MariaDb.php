<?php

namespace Doctrine1\Connection;

class MariaDb extends Mysql
{
    /** @var class-string<\Illuminate\Database\Query\Grammars\Grammar> */
    protected const ILLUMINATE_GRAMMAR_CLASS = \Illuminate\Database\Query\Grammars\MariaDbGrammar::class;
}
