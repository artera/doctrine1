<?php

namespace Doctrine1\Query;

abstract class Part
{
    /**
     * the query object associated with this parser
     */
    protected \Doctrine1\Query $query;

    protected \Doctrine1\Query\Tokenizer $tokenizer;

    /**
     * @param \Doctrine1\Query $query the query object associated with this parser
     */
    public function __construct(\Doctrine1\Query $query, ?\Doctrine1\Query\Tokenizer $tokenizer = null)
    {
        $this->query = $query;

        if (!$tokenizer) {
            $tokenizer = new \Doctrine1\Query\Tokenizer();
        }
        $this->tokenizer = $tokenizer;
    }

    /**
     * @return \Doctrine1\Query $query the query object associated with this parser
     */
    public function getQuery(): \Doctrine1\Query
    {
        return $this->query;
    }
}
