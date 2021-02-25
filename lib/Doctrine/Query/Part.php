<?php

abstract class Doctrine_Query_Part
{
    /**
     * the query object associated with this parser
     */
    protected Doctrine_Query $query;

    protected Doctrine_Query_Tokenizer $tokenizer;

    /**
     * @param Doctrine_Query $query the query object associated with this parser
     */
    public function __construct(Doctrine_Query $query, ?Doctrine_Query_Tokenizer $tokenizer = null)
    {
        $this->query = $query;

        if (!$tokenizer) {
            $tokenizer = new Doctrine_Query_Tokenizer();
        }
        $this->tokenizer = $tokenizer;
    }

    /**
     * @return Doctrine_Query $query the query object associated with this parser
     */
    public function getQuery(): Doctrine_Query
    {
        return $this->query;
    }
}
