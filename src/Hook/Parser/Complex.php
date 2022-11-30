<?php

namespace Doctrine1\Hook\Parser;

abstract class Complex extends \Doctrine1\Hook\Parser
{
    /**
     * @var \Doctrine1\Query\Tokenizer
     */
    protected $tokenizer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tokenizer = new \Doctrine1\Query\Tokenizer();
    }

    /**
     * parse
     * Parses given field and field value to DQL condition
     * and parameters. This method should always return
     * prepared statement conditions (conditions that use
     * placeholders instead of literal values).
     *
     * @param  string $alias component alias
     * @param  string $field the field name
     * @param  mixed  $value the value of the field
     * @return void
     */
    public function parse($alias, $field, $value)
    {
        $this->condition = $this->parseClause($alias, $field, $value);
    }

    /**
     * parseClause
     *
     * @param  string $alias component alias
     * @param  string $field the field name
     * @param  mixed  $value the value of the field
     * @return string
     */
    public function parseClause($alias, $field, $value)
    {
        $parts = $this->tokenizer->quoteExplode($value, ' AND ');

        if (count($parts) > 1) {
            $ret = [];
            foreach ($parts as $part) {
                $ret[] = $this->parseSingle($alias, $field, $part);
            }

            $r = implode(' AND ', $ret);
        } else {
            $parts = $this->tokenizer->quoteExplode($value, ' OR ');
            if (count($parts) > 1) {
                $ret = [];
                foreach ($parts as $part) {
                    $ret[] = $this->parseClause($alias, $field, $part);
                }

                $r = implode(' OR ', $ret);
            } else {
                $ret = $this->parseSingle($alias, $field, $parts[0]);
                return $ret;
            }
        }
        return '(' . $r . ')';
    }

    /**
     * parseSingle
     *
     * @param  string $alias component alias
     * @param  string $field the field name
     * @param  mixed  $value the value of the field
     * @return string
     */
    abstract public function parseSingle($alias, $field, $value);
}
